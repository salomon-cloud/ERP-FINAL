-- ============================================================================
-- SISEN ERP - Enterprise PostgreSQL Schema (Target Database)
-- ============================================================================
-- Author  : SISEN Architecture (Lead Software Architect)
-- Version : 1.0
-- Engine  : PostgreSQL 14+
--
-- This schema is the DATABASE CONTRACT between all module teams.
-- Every table listed here must be ported into a Laravel migration inside the
-- owning module folder (app/Modules/<Module>/Migrations) with EXACTLY the same
-- names (tables and columns). The running application may use MySQL today;
-- ERP.sql defines the canonical target in PostgreSQL.
--
-- Conventions applied everywhere:
--   * id          BIGSERIAL PRIMARY KEY
--   * created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
--   * updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
--   * deleted_at  TIMESTAMPTZ NULL            -- soft delete strategy
--   * created_by  BIGINT NULL REFERENCES users(id)  -- set by HasAuditFields
--   * updated_by  BIGINT NULL REFERENCES users(id)  -- set by HasAuditFields
--   * row_version INTEGER NOT NULL DEFAULT 1  -- optimistic lock (core docs only)
--   * money       NUMERIC(18,2)               -- never DOUBLE PRECISION
--   * rates/qty   NUMERIC(18,6)
--   * enumerations via CHECK constraints (portable between PostgreSQL/MySQL)
--   * unique natural keys via PARTIAL UNIQUE INDEX (WHERE deleted_at IS NULL)
--   * every FK indexed; every filtered status column indexed
--
-- No INSERT statements are present in this file. Seed data is provided by
-- Laravel seeders (see docs/PLANNING.md - Roles & Permissions).
-- ============================================================================

BEGIN;

-- ============================================================================
-- SECTION 1 - FOUNDATION (Shared module)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- organizations: the tenant / company that runs the ERP instance.
-- Reserved for multi-company support; single company today (nullable on core
-- transactional tables to avoid breaking the single-company flow).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS organizations (
    id                      BIGSERIAL PRIMARY KEY,
    code                    VARCHAR(20)  NOT NULL,
    name                    VARCHAR(200) NOT NULL,
    tax_id                  VARCHAR(30),
    legal_name              VARCHAR(200),
    address                 TEXT,
    phone                   VARCHAR(30),
    email                   VARCHAR(150),
    logo_url                VARCHAR(500),
    currency_code           CHAR(3)      NOT NULL DEFAULT 'MXN',
    fiscal_year_start_month SMALLINT     NOT NULL DEFAULT 1 CHECK (fiscal_year_start_month BETWEEN 1 AND 12),
    is_active               BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMPTZ,
    created_by              BIGINT REFERENCES users(id),
    updated_by              BIGINT REFERENCES users(id)
);

-- ----------------------------------------------------------------------------
-- users: application users. Compatible with the SISEN v1 users table
-- (name, email, password, role, estado) plus the enterprise extensions.
-- The 'role' column is the LEGACY single-role column; new modules read roles
-- from user_roles. Password uses bcrypt rounds=12 (as configured).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                    BIGSERIAL PRIMARY KEY,
    organization_id       BIGINT REFERENCES organizations(id),
    empleado_id           BIGINT,                 -- legacy v1 link (FK added in HR section)
    name                  VARCHAR(200) NOT NULL,
    email                 VARCHAR(150) NOT NULL,
    password_hash         VARCHAR(255) NOT NULL,
    phone                 VARCHAR(30),
    avatar_url            VARCHAR(500),
    role                  VARCHAR(50),            -- LEGACY v1 column (Administrador | Recursos Humanos | Contador | Empleado)
    status                VARCHAR(20)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','locked')),
    email_verified_at     TIMESTAMPTZ,
    must_change_password  BOOLEAN      NOT NULL DEFAULT FALSE,
    password_changed_at   TIMESTAMPTZ,
    last_login_at         TIMESTAMPTZ,
    failed_login_attempts INTEGER      NOT NULL DEFAULT 0,
    locked_at             TIMESTAMPTZ,
    remember_token        VARCHAR(100),
    created_at            TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at            TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at            TIMESTAMPTZ,
    created_by            BIGINT REFERENCES users(id),
    updated_by            BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_active
    ON users (email) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_users_status ON users (status);
CREATE INDEX IF NOT EXISTS idx_users_organization ON users (organization_id);

-- ----------------------------------------------------------------------------
-- roles: named sets of permissions. Baseline roles seeded by Laravel seeder:
-- Administrador, Recursos Humanos, Contador, Empleado, Ventas, Compras,
-- Almacenista. is_system rows cannot be deleted.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          BIGSERIAL PRIMARY KEY,
    code        VARCHAR(50)  NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    is_system   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_roles_code_active
    ON roles (code) WHERE deleted_at IS NULL;

-- ----------------------------------------------------------------------------
-- permissions: atomic capabilities coded <module>.<entity>.<action>, e.g.
-- 'finance.invoices.post', 'sales.orders.cancel', 'inventory.stock.view'.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS permissions (
    id          BIGSERIAL PRIMARY KEY,
    code        VARCHAR(100) NOT NULL,
    module      VARCHAR(50)  NOT NULL,
    description TEXT,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_permissions_code_active
    ON permissions (code) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_permissions_module ON permissions (module);

-- ----------------------------------------------------------------------------
-- role_permissions: many-to-many role <-> permission.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (role_id, permission_id)
);

CREATE INDEX IF NOT EXISTS idx_role_permissions_permission ON role_permissions (permission_id);

-- ----------------------------------------------------------------------------
-- user_roles: many-to-many user <-> role. Replaces the legacy users.role
-- column for new modules.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_roles (
    user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id    BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (user_id, role_id)
);

CREATE INDEX IF NOT EXISTS idx_user_roles_role ON user_roles (role_id);

-- ----------------------------------------------------------------------------
-- audit_logs: append-only audit trail. Written by the HasAuditTrail trait.
-- Never updated or deleted. JSONB stores field-level old/new snapshots.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT REFERENCES users(id) ON DELETE SET NULL,
    module      VARCHAR(50)  NOT NULL,
    action      VARCHAR(30)  NOT NULL,   -- created|updated|deleted|posted|approved|rejected|cancelled|voided|...
    entity_type VARCHAR(100) NOT NULL,
    entity_id   BIGINT       NOT NULL,
    old_values  JSONB,
    new_values  JSONB,
    ip_address  VARCHAR(45),
    user_agent  TEXT,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_audit_logs_entity
    ON audit_logs (entity_type, entity_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON audit_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_module ON audit_logs (module, created_at DESC);

-- ----------------------------------------------------------------------------
-- notifications: in-app notifications (DB driver). Optional email delivery
-- when MAIL_MAILER is configured. Persistent; read_at marks consumed.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         BIGSERIAL PRIMARY KEY,
    user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type       VARCHAR(50)  NOT NULL,   -- low_stock|leave_approved|order_posted|bill_due|payroll_processed|mention|...
    title      VARCHAR(200) NOT NULL,
    body       TEXT,
    data       JSONB,
    read_at    TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_notifications_user_unread
    ON notifications (user_id, read_at) WHERE read_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_notifications_created ON notifications (created_at DESC);

-- ----------------------------------------------------------------------------
-- settings: key/value application configuration (group.key). Values may be
-- JSON (is_json=TRUE). Read via Settings facade; write guarded by permission.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id          BIGSERIAL PRIMARY KEY,
    group_name  VARCHAR(50)  NOT NULL,   -- company|finance|inventory|sales|notifications|security
    key         VARCHAR(100) NOT NULL,
    value       TEXT,
    is_json     BOOLEAN      NOT NULL DEFAULT FALSE,
    description TEXT,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id),
    CONSTRAINT uq_settings_group_key UNIQUE (group_name, key)
);

-- ----------------------------------------------------------------------------
-- catalogs: generic lookup values shared across modules.
-- 'group' enumerates the value-list type (payment_terms, payment_method,
-- currency, contract_type, leave_type, document_status, ...).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalogs (
    id          BIGSERIAL PRIMARY KEY,
    group_name  VARCHAR(50)  NOT NULL,
    code        VARCHAR(50)  NOT NULL,
    name        VARCHAR(150) NOT NULL,
    value       VARCHAR(255),
    sort_order  INTEGER      NOT NULL DEFAULT 0,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id),
    CONSTRAINT uq_catalogs_group_code UNIQUE (group_name, code)
);

CREATE INDEX IF NOT EXISTS idx_catalogs_group_active ON catalogs (group_name, is_active, sort_order);

-- ----------------------------------------------------------------------------
-- attachments: polymorphic file references (disk/path). Uploaded via the
-- shared AttachmentService; MIME/type whitelist enforced at the service layer.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attachments (
    id            BIGSERIAL PRIMARY KEY,
    model_type    VARCHAR(100) NOT NULL,
    model_id      BIGINT       NOT NULL,
    disk          VARCHAR(50)  NOT NULL DEFAULT 'public',
    path          VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type     VARCHAR(120),
    size_bytes    BIGINT,
    uploaded_by   BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at    TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_attachments_model ON attachments (model_type, model_id);

-- ----------------------------------------------------------------------------
-- tags + taggables: lightweight user organization labels (polymorphic).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tags (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(80)  NOT NULL,
    slug       VARCHAR(100) NOT NULL,
    color      VARCHAR(20),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,
    CONSTRAINT uq_tags_slug UNIQUE (slug)
);

CREATE TABLE IF NOT EXISTS taggables (
    tag_id         BIGINT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    taggable_type  VARCHAR(100) NOT NULL,
    taggable_id    BIGINT       NOT NULL,
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    PRIMARY KEY (tag_id, taggable_type, taggable_id)
);

CREATE INDEX IF NOT EXISTS idx_taggables_entity ON taggables (taggable_type, taggable_id);

-- ----------------------------------------------------------------------------
-- favorites: per-user bookmarks (polymorphic).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favorites (
    user_id          BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    favoritable_type VARCHAR(100) NOT NULL,
    favoritable_id   BIGINT       NOT NULL,
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    PRIMARY KEY (user_id, favoritable_type, favoritable_id)
);

CREATE INDEX IF NOT EXISTS idx_favorites_entity ON favorites (favoritable_type, favoritable_id);

-- ----------------------------------------------------------------------------
-- comments: threaded discussion on any entity (quotes, orders, leaves, ...).
-- parent_id enables replies; soft delete.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
    id          BIGSERIAL PRIMARY KEY,
    commentable_type VARCHAR(100) NOT NULL,
    commentable_id   BIGINT       NOT NULL,
    user_id     BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    parent_id   BIGINT REFERENCES comments(id) ON DELETE CASCADE,
    body        TEXT   NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_comments_entity ON comments (commentable_type, commentable_id, created_at);
CREATE INDEX IF NOT EXISTS idx_comments_parent ON comments (parent_id);

-- ----------------------------------------------------------------------------
-- document_sequences: THE ONLY source of human-readable document numbers.
-- Atomic increment per (module, prefix). Cancelled numbers are never reused.
-- Example rows are configured per module (e.g. module='sales_orders', prefix='SO-').
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS document_sequences (
    id              BIGSERIAL PRIMARY KEY,
    module          VARCHAR(50)  NOT NULL,   -- sales_orders, sales_invoices, ...
    prefix          VARCHAR(10)  NOT NULL,
    suffix          VARCHAR(10)  NOT NULL DEFAULT '',
    current_number  BIGINT       NOT NULL DEFAULT 0,
    digits_padding  SMALLINT     NOT NULL DEFAULT 6,  -- SO-000123
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_sequences_module_prefix UNIQUE (module, prefix)
);

-- ----------------------------------------------------------------------------
-- import_batches: staging for CSV imports (shared ImportService). Rows keep
-- the raw payload + validation errors so failures never corrupt master data.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS import_batches (
    id              BIGSERIAL PRIMARY KEY,
    module          VARCHAR(50) NOT NULL,
    template        VARCHAR(100) NOT NULL,
    file_path       VARCHAR(500),
    status          VARCHAR(20) NOT NULL DEFAULT 'uploaded' CHECK (status IN ('uploaded','validating','imported','failed','cancelled')),
    total_rows      INTEGER NOT NULL DEFAULT 0,
    inserted_rows   INTEGER NOT NULL DEFAULT 0,
    updated_rows    INTEGER NOT NULL DEFAULT 0,
    skipped_rows    INTEGER NOT NULL DEFAULT 0,
    error_summary   JSONB,
    imported_by     BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    completed_at    TIMESTAMPTZ
);

-- ----------------------------------------------------------------------------
-- saved_reports: user bookmarks for configured reports (name + filter config).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS saved_reports (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    module      VARCHAR(50)  NOT NULL,
    report_type VARCHAR(50)  NOT NULL,   -- trial_balance, stock_levels, ...
    config      JSONB,
    is_shared   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_by  BIGINT REFERENCES users(id) ON DELETE CASCADE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_saved_reports_owner ON saved_reports (created_by, module);
PLANEOF
echo "ERP.sql chunk 1 done"
-- ============================================================================
-- SECTION 2 - FINANCE & ACCOUNTING (Finance module)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- chart_of_accounts: hierarchical account catalog. Leaf accounts (is_header =
-- FALSE) accept journal lines; headers organize the tree. normal_balance drives
-- the sign convention in reports.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id               BIGSERIAL PRIMARY KEY,
    organization_id  BIGINT REFERENCES organizations(id),
    parent_id        BIGINT REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    code             VARCHAR(30)  NOT NULL,
    name             VARCHAR(200) NOT NULL,
    account_type     VARCHAR(30)  NOT NULL CHECK (account_type IN
                        ('asset','liability','equity','revenue','expense',
                         'contra_asset','contra_liability','contra_equity',
                         'contra_revenue','contra_expense')),
    normal_balance   VARCHAR(10)  NOT NULL CHECK (normal_balance IN ('debit','credit')),
    is_header        BOOLEAN      NOT NULL DEFAULT FALSE,
    allow_transactions BOOLEAN    NOT NULL DEFAULT TRUE,
    currency_code    CHAR(3)      NOT NULL DEFAULT 'MXN',
    is_active        BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ,
    created_by       BIGINT REFERENCES users(id),
    updated_by       BIGINT REFERENCES users(id),
    CONSTRAINT chk_account_header_rule CHECK (is_header = FALSE OR allow_transactions = FALSE)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_coa_code_active ON chart_of_accounts (code) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_coa_parent ON chart_of_accounts (parent_id);
CREATE INDEX IF NOT EXISTS idx_coa_type ON chart_of_accounts (account_type);

-- ----------------------------------------------------------------------------
-- fiscal_periods: accounting periods. Entries post only into 'open' periods.
-- Closing locks the period; a closed period can be reopened only by Admin
-- (audited). is_closing_period marks the retained-earnings closing entry period.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fiscal_periods (
    id                 BIGSERIAL PRIMARY KEY,
    organization_id    BIGINT REFERENCES organizations(id),
    name               VARCHAR(80)  NOT NULL,
    year               SMALLINT     NOT NULL,
    start_date         DATE         NOT NULL,
    end_date           DATE         NOT NULL,
    status             VARCHAR(20)  NOT NULL DEFAULT 'open' CHECK (status IN ('open','closed','locked')),
    is_closing_period  BOOLEAN      NOT NULL DEFAULT FALSE,
    closed_by          BIGINT REFERENCES users(id),
    closed_at          TIMESTAMPTZ,
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at         TIMESTAMPTZ,
    created_by         BIGINT REFERENCES users(id),
    updated_by         BIGINT REFERENCES users(id),
    CONSTRAINT chk_period_dates CHECK (end_date >= start_date),
    CONSTRAINT uq_fiscal_period_year_name UNIQUE (year, name)
);

CREATE INDEX IF NOT EXISTS idx_fiscal_periods_status ON fiscal_periods (status);

-- ----------------------------------------------------------------------------
-- cost_centers: optional reporting dimension on journal lines / budgets.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cost_centers (
    id          BIGSERIAL PRIMARY KEY,
    parent_id   BIGINT REFERENCES cost_centers(id) ON DELETE RESTRICT,
    code        VARCHAR(30)  NOT NULL,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_cost_centers_code_active ON cost_centers (code) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_cost_centers_parent ON cost_centers (parent_id);

-- ----------------------------------------------------------------------------
-- journal_entries: the heart of the accounting system. status lifecycle:
-- draft -> posted -> void. posted entries can only be voided (never deleted),
-- which creates a reversing entry. entry_number is reserved at creation from
-- document_sequences (module='journal_entries', prefix='JE-').
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS journal_entries (
    id               BIGSERIAL PRIMARY KEY,
    organization_id  BIGINT REFERENCES organizations(id),
    entry_number     VARCHAR(30)  NOT NULL,
    fiscal_period_id BIGINT NOT NULL REFERENCES fiscal_periods(id) ON DELETE RESTRICT,
    entry_date       DATE   NOT NULL,
    description      VARCHAR(500) NOT NULL,
    reference        VARCHAR(120),
    source_type      VARCHAR(30)  NOT NULL CHECK (source_type IN
                        ('manual','invoice','credit_note','payment','vendor_bill',
                         'purchase_return','payroll','closing','reconciliation',
                         'adjustment')),
    source_id        BIGINT,               -- document id in the source module
    status           VARCHAR(20)  NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','posted','void')),
    total_debit      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total_debit >= 0),
    total_credit     NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total_credit >= 0),
    posted_by        BIGINT REFERENCES users(id),
    posted_at        TIMESTAMPTZ,
    voided_by        BIGINT REFERENCES users(id),
    void_reason      VARCHAR(500),
    row_version      INTEGER      NOT NULL DEFAULT 1,   -- optimistic lock
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ,
    created_by       BIGINT REFERENCES users(id),
    updated_by       BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_journal_entries_number ON journal_entries (entry_number);
CREATE INDEX IF NOT EXISTS idx_journal_entries_period ON journal_entries (fiscal_period_id, entry_date);
CREATE INDEX IF NOT EXISTS idx_journal_entries_status ON journal_entries (status);
CREATE INDEX IF NOT EXISTS idx_journal_entries_source ON journal_entries (source_type, source_id);

-- ----------------------------------------------------------------------------
-- journal_entry_lines: debit/credit lines of an entry. A line is EITHER debit
-- OR credit (never both, never negative). Each line carries its own fiscal
-- period so late adjustments can be reposted correctly.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS journal_entry_lines (
    id               BIGSERIAL PRIMARY KEY,
    journal_entry_id BIGINT NOT NULL REFERENCES journal_entries(id) ON DELETE CASCADE,
    account_id       BIGINT NOT NULL REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    fiscal_period_id BIGINT NOT NULL REFERENCES fiscal_periods(id) ON DELETE RESTRICT,
    cost_center_id   BIGINT REFERENCES cost_centers(id) ON DELETE SET NULL,
    description      VARCHAR(500),
    debit            NUMERIC(18,2) NOT NULL DEFAULT 0,
    credit           NUMERIC(18,2) NOT NULL DEFAULT 0,
    reference        VARCHAR(120),
    created_at       TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_jel_single_side CHECK
        ((debit > 0 AND credit = 0) OR (debit = 0 AND credit > 0)),
    CONSTRAINT chk_jel_no_negative CHECK (debit >= 0 AND credit >= 0)
);

CREATE INDEX IF NOT EXISTS idx_jel_entry ON journal_entry_lines (journal_entry_id);
CREATE INDEX IF NOT EXISTS idx_jel_account ON journal_entry_lines (account_id, fiscal_period_id);
CREATE INDEX IF NOT EXISTS idx_jel_cost_center ON journal_entry_lines (cost_center_id);

-- ----------------------------------------------------------------------------
-- budgets + budget_lines: per period/cost-center, projected vs actual.
-- actual_amount is filled at read time from posted journal lines (team view).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS budgets (
    id               BIGSERIAL PRIMARY KEY,
    organization_id  BIGINT REFERENCES organizations(id),
    fiscal_period_id BIGINT NOT NULL REFERENCES fiscal_periods(id) ON DELETE RESTRICT,
    cost_center_id   BIGINT REFERENCES cost_centers(id) ON DELETE SET NULL,
    name             VARCHAR(150) NOT NULL,
    status           VARCHAR(20)  NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','approved','closed')),
    total_amount     NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total_amount >= 0),
    approved_by      BIGINT REFERENCES users(id),
    approved_at      TIMESTAMPTZ,
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ,
    created_by       BIGINT REFERENCES users(id),
    updated_by       BIGINT REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS budget_lines (
    id              BIGSERIAL PRIMARY KEY,
    budget_id       BIGINT NOT NULL REFERENCES budgets(id) ON DELETE CASCADE,
    account_id      BIGINT NOT NULL REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    period_month    SMALLINT NOT NULL CHECK (period_month BETWEEN 1 AND 12),
    projected_amount NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (projected_amount >= 0),
    actual_amount   NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (actual_amount >= 0),
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_budget_line_account UNIQUE (budget_id, account_id, period_month)
);

CREATE INDEX IF NOT EXISTS idx_budget_lines_account ON budget_lines (account_id);

-- ----------------------------------------------------------------------------
-- bank_accounts: company bank accounts (checking/savings/credit_card).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_accounts (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    name            VARCHAR(150) NOT NULL,
    bank_name       VARCHAR(150) NOT NULL,
    account_number  VARCHAR(60)  NOT NULL,
    account_type    VARCHAR(20)  NOT NULL DEFAULT 'checking' CHECK (account_type IN ('checking','savings','credit_card')),
    currency_code   CHAR(3)      NOT NULL DEFAULT 'MXN',
    opening_balance NUMERIC(18,2) NOT NULL DEFAULT 0,
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

-- ----------------------------------------------------------------------------
-- bank_transactions: statement lines imported/registered for reconciliation.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_transactions (
    id                BIGSERIAL PRIMARY KEY,
    bank_account_id   BIGINT NOT NULL REFERENCES bank_accounts(id) ON DELETE CASCADE,
    statement_date    DATE   NOT NULL,
    description       VARCHAR(300) NOT NULL,
    amount            NUMERIC(18,2) NOT NULL CHECK (amount <> 0),
    status            VARCHAR(20) NOT NULL DEFAULT 'unmatched' CHECK (status IN ('unmatched','matched','pending','void')),
    reference         VARCHAR(120),
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at        TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_bank_transactions_account_date ON bank_transactions (bank_account_id, statement_date);
CREATE INDEX IF NOT EXISTS idx_bank_transactions_status ON bank_transactions (status);

-- ----------------------------------------------------------------------------
-- bank_reconciliations + reconciliation_lines: match bank statement lines to
-- journal entries (payments/invoices/vendor bills).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_reconciliations (
    id              BIGSERIAL PRIMARY KEY,
    bank_account_id BIGINT NOT NULL REFERENCES bank_accounts(id) ON DELETE CASCADE,
    period_start    DATE NOT NULL,
    period_end      DATE NOT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'open' CHECK (status IN ('open','reconciled','closed')),
    opening_balance NUMERIC(18,2) NOT NULL DEFAULT 0,
    closing_balance NUMERIC(18,2) NOT NULL DEFAULT 0,
    reconciled_by   BIGINT REFERENCES users(id),
    reconciled_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id),
    CONSTRAINT chk_recon_dates CHECK (period_end >= period_start)
);

CREATE TABLE IF NOT EXISTS reconciliation_lines (
    id                     BIGSERIAL PRIMARY KEY,
    bank_reconciliation_id BIGINT NOT NULL REFERENCES bank_reconciliations(id) ON DELETE CASCADE,
    bank_transaction_id    BIGINT NOT NULL REFERENCES bank_transactions(id) ON DELETE RESTRICT,
    journal_entry_id       BIGINT REFERENCES journal_entries(id) ON DELETE RESTRICT,
    matched_by             BIGINT REFERENCES users(id),
    matched_at             TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_recon_line UNIQUE (bank_reconciliation_id, bank_transaction_id)
);

-- ----------------------------------------------------------------------------
-- taxes: tax definitions (IVA, ISR withholding, ...). Documents reference
-- taxes.id in their lines. rate stored as fraction-percent value (16.000000).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS taxes (
    id          BIGSERIAL PRIMARY KEY,
    code        VARCHAR(30)   NOT NULL,
    name        VARCHAR(150)  NOT NULL,
    rate        NUMERIC(18,6) NOT NULL CHECK (rate >= 0),
    type        VARCHAR(20)   NOT NULL CHECK (type IN ('vat','withholding','stamp_duty','other')),
    is_active   BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_taxes_code_active ON taxes (code) WHERE deleted_at IS NULL;

-- ----------------------------------------------------------------------------
-- electronic_invoices: CFDI-oriented electronic invoice records. Links to the
-- issuing document via polymorphic (model_type/model_id). SAT stamping is a
-- future integration; table is ready for uuid/xml/stamp response.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS electronic_invoices (
    id                 BIGSERIAL PRIMARY KEY,
    organization_id    BIGINT REFERENCES organizations(id),
    series             VARCHAR(10),
    folio              VARCHAR(30)  NOT NULL,
    document_type      VARCHAR(20)  NOT NULL CHECK (document_type IN ('factura','credit_note','debit_note','trash')),
    model_type         VARCHAR(100),
    model_id           BIGINT,
    uuid               VARCHAR(80),
    xml_path           VARCHAR(500),
    pdf_path           VARCHAR(500),
    status             VARCHAR(30)  NOT NULL DEFAULT 'generated' CHECK (status IN ('generated','stamped','voided','cancelled')),
    stamp_response     JSONB,
    cancelled_at       TIMESTAMPTZ,
    cancellation_uuid  VARCHAR(80),
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at         TIMESTAMPTZ,
    created_by         BIGINT REFERENCES users(id),
    updated_by         BIGINT REFERENCES users(id),
    CONSTRAINT uq_electronic_invoices_folio UNIQUE (series, folio)
);

CREATE INDEX IF NOT EXISTS idx_einvoice_entity ON electronic_invoices (model_type, model_id);
CREATE INDEX IF NOT EXISTS idx_einvoice_status ON electronic_invoices (status);

-- ----------------------------------------------------------------------------
-- currency_exchange_rates: daily rates for multi-currency support (baseline).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS currency_exchange_rates (
    id             BIGSERIAL PRIMARY KEY,
    from_currency  CHAR(3)   NOT NULL,
    to_currency    CHAR(3)   NOT NULL,
    effective_date DATE      NOT NULL,
    rate           NUMERIC(18,6) NOT NULL CHECK (rate > 0),
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_currency_pair_date UNIQUE (from_currency, to_currency, effective_date)
);

-- ============================================================================
-- SECTION 3 - SALES (Sales module)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- customers: sales master data. Owned by Sales; referenced by CRM
-- (opportunities, customer history).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id               BIGSERIAL PRIMARY KEY,
    organization_id  BIGINT REFERENCES organizations(id),
    code             VARCHAR(30)  NOT NULL,
    name             VARCHAR(200) NOT NULL,
    legal_name       VARCHAR(200),
    tax_id           VARCHAR(30),
    email            VARCHAR(150),
    phone            VARCHAR(30),
    address          TEXT,
    credit_limit     NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (credit_limit >= 0),
    payment_term_id  BIGINT REFERENCES catalogs(id) ON DELETE SET NULL,   -- group 'payment_terms'
    price_list_id    BIGINT REFERENCES price_lists(id) ON DELETE SET NULL,
    currency_code    CHAR(3)      NOT NULL DEFAULT 'MXN',
    status           VARCHAR(20)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ,
    created_by       BIGINT REFERENCES users(id),
    updated_by       BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_customers_code_active ON customers (code) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_customers_status ON customers (status);
CREATE INDEX IF NOT EXISTS idx_customers_tax_id ON customers (tax_id);

-- ----------------------------------------------------------------------------
-- price_lists + price_list_items: catalog prices; effective price is always
-- snapshotted into document line unit_price at creation time.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS price_lists (
    id          BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    code        VARCHAR(30)  NOT NULL,
    name        VARCHAR(150) NOT NULL,
    currency_code CHAR(3)    NOT NULL DEFAULT 'MXN',
    is_default  BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_price_lists_code_active ON price_lists (code) WHERE deleted_at IS NULL;

CREATE TABLE IF NOT EXISTS price_list_items (
    id            BIGSERIAL PRIMARY KEY,
    price_list_id BIGINT NOT NULL REFERENCES price_lists(id) ON DELETE CASCADE,
    product_id    BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    min_quantity  NUMERIC(18,6) NOT NULL DEFAULT 1 CHECK (min_quantity > 0),
    price         NUMERIC(18,2) NOT NULL CHECK (price >= 0),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_price_list_item UNIQUE (price_list_id, product_id, min_quantity)
);

CREATE INDEX IF NOT EXISTS idx_price_list_items_product ON price_list_items (product_id);

-- ----------------------------------------------------------------------------
-- sales_quotes + sales_quote_lines: pre-sales documents. Converted to orders.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales_quotes (
    id             BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    quote_number   VARCHAR(30)  NOT NULL,           -- seq 'COT-'
    customer_id    BIGINT REFERENCES customers(id) ON DELETE RESTRICT,
    price_list_id  BIGINT REFERENCES price_lists(id) ON DELETE SET NULL,
    quote_date     DATE         NOT NULL DEFAULT CURRENT_DATE,
    valid_until    DATE,
    status         VARCHAR(20)  NOT NULL DEFAULT 'draft' CHECK (status IN
                        ('draft','sent','accepted','rejected','converted','cancelled')),
    subtotal       NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    discount_total NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_total >= 0),
    tax_total      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_total >= 0),
    total          NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    currency_code  CHAR(3)      NOT NULL DEFAULT 'MXN',
    notes          TEXT,
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at     TIMESTAMPTZ,
    created_by     BIGINT REFERENCES users(id),
    updated_by     BIGINT REFERENCES users(id),
    CONSTRAINT chk_quote_dates CHECK (valid_until IS NULL OR valid_until >= quote_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_sales_quotes_number ON sales_quotes (quote_number);
CREATE INDEX IF NOT EXISTS idx_sales_quotes_customer ON sales_quotes (customer_id, quote_date DESC);
CREATE INDEX IF NOT EXISTS idx_sales_quotes_status ON sales_quotes (status);

CREATE TABLE IF NOT EXISTS sales_quote_lines (
    id           BIGSERIAL PRIMARY KEY,
    sales_quote_id BIGINT NOT NULL REFERENCES sales_quotes(id) ON DELETE CASCADE,
    product_id   BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    description  VARCHAR(300),
    quantity     NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    unit_price   NUMERIC(18,2) NOT NULL CHECK (unit_price >= 0),   -- snapshot
    discount_rate NUMERIC(5,2) NOT NULL DEFAULT 0 CHECK (discount_rate BETWEEN 0 AND 100),
    discount_amount NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    tax_id       BIGINT REFERENCES taxes(id) ON DELETE SET NULL,
    tax_rate     NUMERIC(18,6) NOT NULL DEFAULT 0,                 -- snapshot
    tax_amount   NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    subtotal     NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    total        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_quote_line_discount CHECK (discount_amount = 0 OR discount_rate = 0)
);

CREATE INDEX IF NOT EXISTS idx_sales_quote_lines_quote ON sales_quote_lines (sales_quote_id);

-- ----------------------------------------------------------------------------
-- sales_orders + sales_order_lines: the binding sales document. Lifecycle:
-- draft -> confirmed -> (partial)fulfilled -> invoiced. Stock reservation is
-- done by Inventory ReserveStockService on confirm.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales_orders (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    order_number    VARCHAR(30)  NOT NULL,           -- seq 'SO-'
    quote_id        BIGINT REFERENCES sales_quotes(id) ON DELETE SET NULL,
    customer_id     BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    price_list_id   BIGINT REFERENCES price_lists(id) ON DELETE SET NULL,
    order_date      DATE    NOT NULL DEFAULT CURRENT_DATE,
    expected_date   DATE,
    status          VARCHAR(30)  NOT NULL DEFAULT 'draft' CHECK (status IN
                        ('draft','confirmed','fulfilled','partially_invoiced',
                         'invoiced','cancelled')),
    subtotal        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    discount_total  NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_total >= 0),
    tax_total       NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_total >= 0),
    total           NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    currency_code   CHAR(3)       NOT NULL DEFAULT 'MXN',
    notes           TEXT,
    row_version     INTEGER       NOT NULL DEFAULT 1,
    created_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id),
    CONSTRAINT chk_order_dates CHECK (expected_date IS NULL OR expected_date >= order_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_sales_orders_number ON sales_orders (order_number);
CREATE INDEX IF NOT EXISTS idx_sales_orders_customer ON sales_orders (customer_id, order_date DESC);
CREATE INDEX IF NOT EXISTS idx_sales_orders_status ON sales_orders (status);

CREATE TABLE IF NOT EXISTS sales_order_lines (
    id            BIGSERIAL PRIMARY KEY,
    sales_order_id BIGINT NOT NULL REFERENCES sales_orders(id) ON DELETE CASCADE,
    product_id    BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    warehouse_id  BIGINT REFERENCES warehouses(id) ON DELETE SET NULL,
    description   VARCHAR(300),
    quantity      NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    quantity_delivered NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (quantity_delivered >= 0),
    unit_price    NUMERIC(18,2) NOT NULL CHECK (unit_price >= 0),
    discount_rate NUMERIC(5,2)  NOT NULL DEFAULT 0 CHECK (discount_rate BETWEEN 0 AND 100),
    discount_amount NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    tax_id        BIGINT REFERENCES taxes(id) ON DELETE SET NULL,
    tax_rate      NUMERIC(18,6) NOT NULL DEFAULT 0,
    tax_amount    NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    subtotal      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    total         NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_order_line_delivered CHECK (quantity_delivered <= quantity),
    CONSTRAINT chk_order_line_discount CHECK (discount_amount = 0 OR discount_rate = 0)
);

CREATE INDEX IF NOT EXISTS idx_sales_order_lines_order ON sales_order_lines (sales_order_id);
CREATE INDEX IF NOT EXISTS idx_sales_order_lines_product ON sales_order_lines (product_id);

-- ----------------------------------------------------------------------------
-- sales_invoices + sales_invoice_lines: AR documents. Issued invoices post a
-- journal entry (revenue + tax + AR) via Finance JournalPostingService.
-- paid status is derived from customer_payments allocations.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales_invoices (
    id                BIGSERIAL PRIMARY KEY,
    organization_id   BIGINT REFERENCES organizations(id),
    invoice_number    VARCHAR(30)  NOT NULL,           -- seq 'FC-'
    sales_order_id    BIGINT REFERENCES sales_orders(id) ON DELETE SET NULL,
    customer_id       BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    fiscal_period_id  BIGINT REFERENCES fiscal_periods(id) ON DELETE RESTRICT,
    electronic_invoice_id BIGINT REFERENCES electronic_invoices(id) ON DELETE SET NULL,
    issue_date        DATE    NOT NULL DEFAULT CURRENT_DATE,
    due_date          DATE,
    payment_term_id   BIGINT REFERENCES catalogs(id) ON DELETE SET NULL,
    status            VARCHAR(30)  NOT NULL DEFAULT 'draft' CHECK (status IN
                          ('draft','issued','partially_paid','paid','overdue','cancelled')),
    subtotal          NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    discount_total    NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_total >= 0),
    tax_total         NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_total >= 0),
    total             NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    total_paid        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total_paid >= 0),
    currency_code     CHAR(3)       NOT NULL DEFAULT 'MXN',
    notes             TEXT,
    row_version       INTEGER       NOT NULL DEFAULT 1,
    created_at        TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    deleted_at        TIMESTAMPTZ,
    created_by        BIGINT REFERENCES users(id),
    updated_by        BIGINT REFERENCES users(id),
    CONSTRAINT chk_invoice_paid CHECK (total_paid <= total),
    CONSTRAINT chk_invoice_due CHECK (due_date IS NULL OR due_date >= issue_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_sales_invoices_number ON sales_invoices (invoice_number);
CREATE INDEX IF NOT EXISTS idx_sales_invoices_customer ON sales_invoices (customer_id, issue_date DESC);
CREATE INDEX IF NOT EXISTS idx_sales_invoices_status ON sales_invoices (status);
CREATE INDEX IF NOT EXISTS idx_sales_invoices_period ON sales_invoices (fiscal_period_id);

CREATE TABLE IF NOT EXISTS sales_invoice_lines (
    id               BIGSERIAL PRIMARY KEY,
    sales_invoice_id BIGINT NOT NULL REFERENCES sales_invoices(id) ON DELETE CASCADE,
    sales_order_line_id BIGINT REFERENCES sales_order_lines(id) ON DELETE SET NULL,
    product_id       BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    description      VARCHAR(300),
    quantity         NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    unit_price       NUMERIC(18,2) NOT NULL CHECK (unit_price >= 0),
    discount_rate    NUMERIC(5,2)  NOT NULL DEFAULT 0 CHECK (discount_rate BETWEEN 0 AND 100),
    discount_amount  NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    tax_id           BIGINT REFERENCES taxes(id) ON DELETE SET NULL,
    tax_rate         NUMERIC(18,6) NOT NULL DEFAULT 0,
    tax_amount       NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    subtotal         NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    total            NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_invoice_line_discount CHECK (discount_amount = 0 OR discount_rate = 0)
);

CREATE INDEX IF NOT EXISTS idx_sales_invoice_lines_invoice ON sales_invoice_lines (sales_invoice_id);

-- ----------------------------------------------------------------------------
-- credit_notes + credit_note_lines: reverse an invoice (returns/discounts/errors).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS credit_notes (
    id                BIGSERIAL PRIMARY KEY,
    organization_id   BIGINT REFERENCES organizations(id),
    credit_note_number VARCHAR(30) NOT NULL,           -- seq 'NC-'
    sales_invoice_id  BIGINT NOT NULL REFERENCES sales_invoices(id) ON DELETE RESTRICT,
    customer_id       BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    reason            VARCHAR(30) NOT NULL CHECK (reason IN ('return','discount','error','other')),
    status            VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','issued','cancelled')),
    issue_date        DATE NOT NULL DEFAULT CURRENT_DATE,
    subtotal          NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    tax_total         NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_total >= 0),
    total             NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    row_version       INTEGER      NOT NULL DEFAULT 1,
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at        TIMESTAMPTZ,
    created_by        BIGINT REFERENCES users(id),
    updated_by        BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_credit_notes_number ON credit_notes (credit_note_number);
CREATE INDEX IF NOT EXISTS idx_credit_notes_invoice ON credit_notes (sales_invoice_id);

CREATE TABLE IF NOT EXISTS credit_note_lines (
    id              BIGSERIAL PRIMARY KEY,
    credit_note_id  BIGINT NOT NULL REFERENCES credit_notes(id) ON DELETE CASCADE,
    invoice_line_id BIGINT REFERENCES sales_invoice_lines(id) ON DELETE SET NULL,
    product_id      BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    description     VARCHAR(300),
    quantity        NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    unit_price      NUMERIC(18,2) NOT NULL CHECK (unit_price >= 0),
    tax_id          BIGINT REFERENCES taxes(id) ON DELETE SET NULL,
    tax_rate        NUMERIC(18,6) NOT NULL DEFAULT 0,
    tax_amount      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    subtotal        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    total           NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_credit_note_lines_note ON credit_note_lines (credit_note_id);

-- ----------------------------------------------------------------------------
-- customer_payments: inbound payments (cash/transfer/check/card). invoice_id
-- may be NULL for on-account payments later allocated to invoices.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customer_payments (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    payment_number  VARCHAR(30)  NOT NULL,             -- seq 'PAG-'
    customer_id     BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    invoice_id      BIGINT REFERENCES sales_invoices(id) ON DELETE SET NULL,
    payment_date    DATE   NOT NULL DEFAULT CURRENT_DATE,
    amount          NUMERIC(18,2) NOT NULL CHECK (amount > 0),
    method          VARCHAR(30)  NOT NULL DEFAULT 'cash' CHECK (method IN ('cash','transfer','check','card','payment_link')),
    reference       VARCHAR(120),
    bank_account_id BIGINT REFERENCES bank_accounts(id) ON DELETE SET NULL,
    status          VARCHAR(20)  NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','posted','cancelled')),
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_customer_payments_number ON customer_payments (payment_number);
CREATE INDEX IF NOT EXISTS idx_customer_payments_customer ON customer_payments (customer_id, payment_date DESC);
CREATE INDEX IF NOT EXISTS idx_customer_payments_invoice ON customer_payments (invoice_id);

-- ============================================================================
-- SECTION 4 - PURCHASING (Purchasing module)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- suppliers: purchasing master data.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suppliers (
    id               BIGSERIAL PRIMARY KEY,
    organization_id  BIGINT REFERENCES organizations(id),
    code             VARCHAR(30)  NOT NULL,
    name             VARCHAR(200) NOT NULL,
    legal_name       VARCHAR(200),
    tax_id           VARCHAR(30),
    contact_name     VARCHAR(150),
    email            VARCHAR(150),
    phone            VARCHAR(30),
    address          TEXT,
    payment_term_id  BIGINT REFERENCES catalogs(id) ON DELETE SET NULL,
    currency_code    CHAR(3)      NOT NULL DEFAULT 'MXN',
    status           VARCHAR(20)  NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ,
    created_by       BIGINT REFERENCES users(id),
    updated_by       BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_suppliers_code_active ON suppliers (code) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_suppliers_status ON suppliers (status);

-- ----------------------------------------------------------------------------
-- purchase_requests + purchase_request_lines: internal requisitions.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_requests (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    pr_number       VARCHAR(30) NOT NULL,              -- seq 'PR-'
    department_id   BIGINT REFERENCES departments(id) ON DELETE SET NULL,
    requester_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    required_date   DATE,
    status          VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN
                        ('draft','submitted','approved','rejected','converted','closed')),
    notes           TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_purchase_requests_number ON purchase_requests (pr_number);
CREATE INDEX IF NOT EXISTS idx_purchase_requests_status ON purchase_requests (status);

CREATE TABLE IF NOT EXISTS purchase_request_lines (
    id                  BIGSERIAL PRIMARY KEY,
    purchase_request_id BIGINT NOT NULL REFERENCES purchase_requests(id) ON DELETE CASCADE,
    product_id          BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    quantity_requested  NUMERIC(18,6) NOT NULL CHECK (quantity_requested > 0),
    suggested_supplier_id BIGINT REFERENCES suppliers(id) ON DELETE SET NULL,
    notes               TEXT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_purchase_request_lines_request ON purchase_request_lines (purchase_request_id);

-- ----------------------------------------------------------------------------
-- purchase_orders + purchase_order_lines: binding purchasing documents.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_orders (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    po_number       VARCHAR(30) NOT NULL,              -- seq 'PO-'
    supplier_id     BIGINT NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    request_id      BIGINT REFERENCES purchase_requests(id) ON DELETE SET NULL,
    order_date      DATE   NOT NULL DEFAULT CURRENT_DATE,
    expected_date   DATE,
    currency_code   CHAR(3) NOT NULL DEFAULT 'MXN',
    status          VARCHAR(30) NOT NULL DEFAULT 'draft' CHECK (status IN
                        ('draft','sent','confirmed','received','partially_received','invoiced','cancelled')),
    subtotal        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    discount_total  NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_total >= 0),
    tax_total       NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_total >= 0),
    total           NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    notes           TEXT,
    row_version     INTEGER NOT NULL DEFAULT 1,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id),
    CONSTRAINT chk_po_dates CHECK (expected_date IS NULL OR expected_date >= order_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_purchase_orders_number ON purchase_orders (po_number);
CREATE INDEX IF NOT EXISTS idx_purchase_orders_supplier ON purchase_orders (supplier_id, order_date DESC);
CREATE INDEX IF NOT EXISTS idx_purchase_orders_status ON purchase_orders (status);

CREATE TABLE IF NOT EXISTS purchase_order_lines (
    id              BIGSERIAL PRIMARY KEY,
    purchase_order_id BIGINT NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
    product_id      BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    warehouse_id    BIGINT REFERENCES warehouses(id) ON DELETE SET NULL,
    description     VARCHAR(300),
    quantity        NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    quantity_received NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (quantity_received >= 0),
    unit_cost       NUMERIC(18,2) NOT NULL CHECK (unit_cost >= 0),
    discount_rate   NUMERIC(5,2)  NOT NULL DEFAULT 0 CHECK (discount_rate BETWEEN 0 AND 100),
    discount_amount NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    tax_id          BIGINT REFERENCES taxes(id) ON DELETE SET NULL,
    tax_rate        NUMERIC(18,6) NOT NULL DEFAULT 0,
    tax_amount      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    subtotal        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    total           NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_po_line_received CHECK (quantity_received <= quantity),
    CONSTRAINT chk_po_line_discount CHECK (discount_amount = 0 OR discount_rate = 0)
);

CREATE INDEX IF NOT EXISTS idx_purchase_order_lines_order ON purchase_order_lines (purchase_order_id);
CREATE INDEX IF NOT EXISTS idx_purchase_order_lines_product ON purchase_order_lines (product_id);

-- ----------------------------------------------------------------------------
-- goods_receipts + goods_receipt_lines: receiving. Posting creates inventory
-- IN movements (movement_type='purchase') and updates PO received quantities.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS goods_receipts (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    receipt_number  VARCHAR(30) NOT NULL,              -- seq 'GR-'
    purchase_order_id BIGINT NOT NULL REFERENCES purchase_orders(id) ON DELETE RESTRICT,
    warehouse_id    BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    received_at     DATE   NOT NULL DEFAULT CURRENT_DATE,
    status          VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','posted','cancelled')),
    received_by     BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_goods_receipts_number ON goods_receipts (receipt_number);
CREATE INDEX IF NOT EXISTS idx_goods_receipts_po ON goods_receipts (purchase_order_id);

CREATE TABLE IF NOT EXISTS goods_receipt_lines (
    id                 BIGSERIAL PRIMARY KEY,
    goods_receipt_id   BIGINT NOT NULL REFERENCES goods_receipts(id) ON DELETE CASCADE,
    purchase_order_line_id BIGINT NOT NULL REFERENCES purchase_order_lines(id) ON DELETE RESTRICT,
    product_id         BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    location_id        BIGINT REFERENCES locations(id) ON DELETE SET NULL,
    quantity_received  NUMERIC(18,6) NOT NULL CHECK (quantity_received > 0),
    unit_cost          NUMERIC(18,2) NOT NULL CHECK (unit_cost >= 0),   -- snapshot from PO
    lot_id             BIGINT REFERENCES lots(id) ON DELETE SET NULL,
    serial_number_id   BIGINT REFERENCES serial_numbers(id) ON DELETE SET NULL,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_goods_receipt_lines_receipt ON goods_receipt_lines (goods_receipt_id);

-- ----------------------------------------------------------------------------
-- vendor_bills + vendor_bill_lines: supplier invoices. Posting creates
-- AP + expense + input-tax journal entries. Three-way match PO/receipt/bill.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vendor_bills (
    id               BIGSERIAL PRIMARY KEY,
    organization_id  BIGINT REFERENCES organizations(id),
    bill_number      VARCHAR(30) NOT NULL,             -- seq 'CB-'
    supplier_id      BIGINT NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    purchase_order_id BIGINT REFERENCES purchase_orders(id) ON DELETE SET NULL,
    goods_receipt_id BIGINT REFERENCES goods_receipts(id) ON DELETE SET NULL,
    fiscal_period_id BIGINT REFERENCES fiscal_periods(id) ON DELETE RESTRICT,
    bill_date        DATE   NOT NULL DEFAULT CURRENT_DATE,
    due_date         DATE,
    currency_code    CHAR(3) NOT NULL DEFAULT 'MXN',
    status           VARCHAR(30) NOT NULL DEFAULT 'draft' CHECK (status IN
                         ('draft','posted','partially_paid','paid','cancelled')),
    subtotal         NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    tax_total        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_total >= 0),
    total            NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    total_paid       NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total_paid >= 0),
    notes            TEXT,
    row_version      INTEGER NOT NULL DEFAULT 1,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ,
    created_by       BIGINT REFERENCES users(id),
    updated_by       BIGINT REFERENCES users(id),
    CONSTRAINT chk_bill_paid CHECK (total_paid <= total),
    CONSTRAINT chk_bill_due CHECK (due_date IS NULL OR due_date >= bill_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_vendor_bills_number ON vendor_bills (bill_number);
CREATE INDEX IF NOT EXISTS idx_vendor_bills_supplier ON vendor_bills (supplier_id, bill_date DESC);
CREATE INDEX IF NOT EXISTS idx_vendor_bills_status ON vendor_bills (status);

CREATE TABLE IF NOT EXISTS vendor_bill_lines (
    id              BIGSERIAL PRIMARY KEY,
    vendor_bill_id  BIGINT NOT NULL REFERENCES vendor_bills(id) ON DELETE CASCADE,
    purchase_order_line_id BIGINT REFERENCES purchase_order_lines(id) ON DELETE SET NULL,
    product_id      BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    description     VARCHAR(300),
    quantity        NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    unit_cost       NUMERIC(18,2) NOT NULL CHECK (unit_cost >= 0),
    tax_id          BIGINT REFERENCES taxes(id) ON DELETE SET NULL,
    tax_rate        NUMERIC(18,6) NOT NULL DEFAULT 0,
    tax_amount      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    subtotal        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    total           NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_vendor_bill_lines_bill ON vendor_bill_lines (vendor_bill_id);

-- ----------------------------------------------------------------------------
-- purchase_returns + purchase_return_lines: returns to supplier (reverse bill).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_returns (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    return_number   VARCHAR(30) NOT NULL,              -- seq 'RD-'
    vendor_bill_id  BIGINT NOT NULL REFERENCES vendor_bills(id) ON DELETE RESTRICT,
    supplier_id     BIGINT NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    reason          VARCHAR(30) NOT NULL CHECK (reason IN ('defective','wrong','excess','other')),
    status          VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','posted','cancelled')),
    return_date     DATE   NOT NULL DEFAULT CURRENT_DATE,
    subtotal        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    tax_total       NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_total >= 0),
    total           NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_purchase_returns_number ON purchase_returns (return_number);
CREATE INDEX IF NOT EXISTS idx_purchase_returns_bill ON purchase_returns (vendor_bill_id);

CREATE TABLE IF NOT EXISTS purchase_return_lines (
    id                BIGSERIAL PRIMARY KEY,
    purchase_return_id BIGINT NOT NULL REFERENCES purchase_returns(id) ON DELETE CASCADE,
    bill_line_id      BIGINT REFERENCES vendor_bill_lines(id) ON DELETE SET NULL,
    product_id        BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    quantity          NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    unit_cost         NUMERIC(18,2) NOT NULL CHECK (unit_cost >= 0),
    tax_rate          NUMERIC(18,6) NOT NULL DEFAULT 0,
    tax_amount        NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    subtotal          NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
    total             NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_purchase_return_lines_return ON purchase_return_lines (purchase_return_id);

-- ----------------------------------------------------------------------------
-- supplier_payments: outbound payments to suppliers.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier_payments (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    payment_number  VARCHAR(30) NOT NULL,              -- seq 'PSP-'
    supplier_id     BIGINT NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    bill_id         BIGINT REFERENCES vendor_bills(id) ON DELETE SET NULL,
    payment_date    DATE   NOT NULL DEFAULT CURRENT_DATE,
    amount          NUMERIC(18,2) NOT NULL CHECK (amount > 0),
    method          VARCHAR(30) NOT NULL DEFAULT 'cash' CHECK (method IN ('cash','transfer','check')),
    reference       VARCHAR(120),
    bank_account_id BIGINT REFERENCES bank_accounts(id) ON DELETE SET NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','posted','cancelled')),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_supplier_payments_number ON supplier_payments (payment_number);
CREATE INDEX IF NOT EXISTS idx_supplier_payments_supplier ON supplier_payments (supplier_id, payment_date DESC);
CREATE INDEX IF NOT EXISTS idx_supplier_payments_bill ON supplier_payments (bill_id);

-- ============================================================================
-- SECTION 5 - INVENTORY (Inventory module)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- warehouses + locations: physical storage hierarchy. Stock is tracked at
-- (product, location) granularity via stock_movements.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS warehouses (
    id          BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    code        VARCHAR(30)  NOT NULL,
    name        VARCHAR(150) NOT NULL,
    address     TEXT,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_warehouses_code_active ON warehouses (code) WHERE deleted_at IS NULL;

CREATE TABLE IF NOT EXISTS locations (
    id          BIGSERIAL PRIMARY KEY,
    warehouse_id BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE CASCADE,
    code        VARCHAR(30)  NOT NULL,
    name        VARCHAR(150) NOT NULL,
    is_pickable BOOLEAN      NOT NULL DEFAULT TRUE,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    CONSTRAINT uq_locations_warehouse_code UNIQUE (warehouse_id, code)
);

CREATE INDEX IF NOT EXISTS idx_locations_warehouse ON locations (warehouse_id);

-- ----------------------------------------------------------------------------
-- product_categories: hierarchical product classification.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_categories (
    id          BIGSERIAL PRIMARY KEY,
    parent_id   BIGINT REFERENCES product_categories(id) ON DELETE RESTRICT,
    code        VARCHAR(30)  NOT NULL,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    updated_by  BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_product_categories_code_active ON product_categories (code) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_product_categories_parent ON product_categories (parent_id);

-- ----------------------------------------------------------------------------
-- units_of_measure: UoM with conversion to a base unit per group (base_ratio).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS units_of_measure (
    id         BIGSERIAL PRIMARY KEY,
    code       VARCHAR(10)   NOT NULL,       -- PZA, KG, L, M
    name       VARCHAR(100)  NOT NULL,
    base_ratio NUMERIC(18,6) NOT NULL DEFAULT 1 CHECK (base_ratio > 0),
    is_base    BOOLEAN       NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,
    CONSTRAINT uq_units_code UNIQUE (code)
);

-- ----------------------------------------------------------------------------
-- products: item master. SKU is the natural key. Cost and sale prices are
-- reference values; transactions snapshot their own prices.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    sku             VARCHAR(50)  NOT NULL,
    name            VARCHAR(200) NOT NULL,
    description     TEXT,
    category_id     BIGINT REFERENCES product_categories(id) ON DELETE SET NULL,
    unit_id         BIGINT REFERENCES units_of_measure(id) ON DELETE RESTRICT,
    default_tax_id  BIGINT REFERENCES taxes(id) ON DELETE SET NULL,
    cost_price      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (cost_price >= 0),
    sale_price      NUMERIC(18,2) NOT NULL DEFAULT 0 CHECK (sale_price >= 0),
    min_stock       NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (min_stock >= 0),
    max_stock       NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (max_stock >= 0),
    is_sellable     BOOLEAN       NOT NULL DEFAULT TRUE,
    is_purchasable  BOOLEAN       NOT NULL DEFAULT TRUE,
    is_stockable    BOOLEAN       NOT NULL DEFAULT TRUE,   -- service items = FALSE
    track_serial    BOOLEAN       NOT NULL DEFAULT FALSE,
    status          VARCHAR(20)   NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_products_sku_active ON products (sku) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_products_category ON products (category_id);
CREATE INDEX IF NOT EXISTS idx_products_status ON products (status);

-- ----------------------------------------------------------------------------
-- product_barcodes: multiple barcodes per product (POS/hardware support).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_barcodes (
    id         BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    barcode    VARCHAR(80) NOT NULL,
    is_primary BOOLEAN     NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_product_barcodes_barcode UNIQUE (barcode)
);

CREATE INDEX IF NOT EXISTS idx_product_barcodes_product ON product_barcodes (product_id);

-- ----------------------------------------------------------------------------
-- lots + serial_numbers: traceability. Movements optionally carry lot/serial.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lots (
    id         BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    lot_number VARCHAR(60) NOT NULL,
    expiry_date DATE,
    is_active  BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,
    CONSTRAINT uq_lots_product_number UNIQUE (product_id, lot_number)
);

CREATE INDEX IF NOT EXISTS idx_lots_expiry ON lots (expiry_date) WHERE expiry_date IS NOT NULL;

CREATE TABLE IF NOT EXISTS serial_numbers (
    id            BIGSERIAL PRIMARY KEY,
    product_id    BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    serial_number VARCHAR(60) NOT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'in_stock' CHECK (status IN
                      ('in_stock','sold','warranty','returned','scrapped')),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at    TIMESTAMPTZ,
    CONSTRAINT uq_serials_product_number UNIQUE (product_id, serial_number)
);

-- ----------------------------------------------------------------------------
-- stock_movements: THE inventory ledger. Every stock change is a row here;
-- current stock is derived (see v_stock_levels). Cancelling a posted movement
-- posts an equal-and-opposite reversal. NEVER update quantities in place.
-- quantity is SIGNED: positive = in, negative = out (in base unit).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_movements (
    id                BIGSERIAL PRIMARY KEY,
    organization_id   BIGINT REFERENCES organizations(id),
    product_id        BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    warehouse_id      BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    location_id       BIGINT REFERENCES locations(id) ON DELETE SET NULL,
    movement_type     VARCHAR(30)  NOT NULL CHECK (movement_type IN
                          ('purchase','sale','transfer_in','transfer_out',
                           'adjustment_in','adjustment_out','return_in',
                           'return_out','count','initial','reservation',
                           'reservation_release')),
    quantity          NUMERIC(18,6) NOT NULL,   -- signed, base unit, <> 0
    unit_cost         NUMERIC(18,6) NOT NULL DEFAULT 0,
    reference_type    VARCHAR(100),
    reference_id      BIGINT,
    lot_id            BIGINT REFERENCES lots(id) ON DELETE SET NULL,
    serial_number_id  BIGINT REFERENCES serial_numbers(id) ON DELETE SET NULL,
    status            VARCHAR(20)  NOT NULL DEFAULT 'posted' CHECK (status IN ('posted','cancelled')),
    posted_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    posted_by         BIGINT REFERENCES users(id),
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at        TIMESTAMPTZ,
    CONSTRAINT chk_stock_movement_qty CHECK (quantity <> 0),
    CONSTRAINT chk_stock_movement_cost CHECK (unit_cost >= 0)
);

CREATE INDEX IF NOT EXISTS idx_stock_movements_product ON stock_movements (product_id, posted_at DESC);
CREATE INDEX IF NOT EXISTS idx_stock_movements_warehouse ON stock_movements (warehouse_id, location_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_type ON stock_movements (movement_type);
CREATE INDEX IF NOT EXISTS idx_stock_movements_reference ON stock_movements (reference_type, reference_id);

-- ----------------------------------------------------------------------------
-- stock_transfers + stock_transfer_lines: inter-warehouse transfers.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_transfers (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    transfer_number VARCHAR(30) NOT NULL,              -- seq 'TR-'
    from_warehouse_id BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    to_warehouse_id   BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    status          VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN
                        ('draft','in_transit','received','cancelled')),
    requested_by    BIGINT REFERENCES users(id),
    approved_by     BIGINT REFERENCES users(id),
    approved_at     TIMESTAMPTZ,
    transferred_at  TIMESTAMPTZ,
    received_at     TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id),
    CONSTRAINT chk_transfer_warehouses CHECK (from_warehouse_id <> to_warehouse_id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_stock_transfers_number ON stock_transfers (transfer_number);

CREATE TABLE IF NOT EXISTS stock_transfer_lines (
    id               BIGSERIAL PRIMARY KEY,
    stock_transfer_id BIGINT NOT NULL REFERENCES stock_transfers(id) ON DELETE CASCADE,
    product_id       BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    quantity         NUMERIC(18,6) NOT NULL CHECK (quantity > 0),
    unit_cost        NUMERIC(18,6) NOT NULL DEFAULT 0,
    lot_id           BIGINT REFERENCES lots(id) ON DELETE SET NULL,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_stock_transfer_lines_transfer ON stock_transfer_lines (stock_transfer_id);

-- ----------------------------------------------------------------------------
-- stock_adjustments + stock_adjustment_lines: cycle adjustments with approval.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_adjustments (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    adjustment_number VARCHAR(30) NOT NULL,            -- seq 'AJ-'
    reason          VARCHAR(500) NOT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','posted','cancelled')),
    approved_by     BIGINT REFERENCES users(id),
    approved_at     TIMESTAMPTZ,
    posted_at       TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_stock_adjustments_number ON stock_adjustments (adjustment_number);
CREATE INDEX IF NOT EXISTS idx_stock_adjustments_status ON stock_adjustments (status);

CREATE TABLE IF NOT EXISTS stock_adjustment_lines (
    id                   BIGSERIAL PRIMARY KEY,
    stock_adjustment_id  BIGINT NOT NULL REFERENCES stock_adjustments(id) ON DELETE CASCADE,
    product_id           BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    warehouse_id         BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    location_id          BIGINT REFERENCES locations(id) ON DELETE SET NULL,
    quantity_difference  NUMERIC(18,6) NOT NULL,       -- signed; <> 0
    unit_cost            NUMERIC(18,6) NOT NULL DEFAULT 0,
    reason               VARCHAR(500),
    created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_adjustment_diff CHECK (quantity_difference <> 0)
);

CREATE INDEX IF NOT EXISTS idx_stock_adjustment_lines_adjustment ON stock_adjustment_lines (stock_adjustment_id);

-- ----------------------------------------------------------------------------
-- inventory_counts + inventory_count_lines: physical count workflow.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory_counts (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT REFERENCES organizations(id),
    count_number    VARCHAR(30) NOT NULL,              -- seq 'INV-'
    warehouse_id    BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    location_id     BIGINT REFERENCES locations(id) ON DELETE SET NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN
                        ('draft','in_progress','counted','adjusted','closed')),
    counted_by      BIGINT REFERENCES users(id),
    counted_at      TIMESTAMPTZ,
    closed_by       BIGINT REFERENCES users(id),
    closed_at       TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_inventory_counts_number ON inventory_counts (count_number);
CREATE INDEX IF NOT EXISTS idx_inventory_counts_status ON inventory_counts (status);

CREATE TABLE IF NOT EXISTS inventory_count_lines (
    id                 BIGSERIAL PRIMARY KEY,
    inventory_count_id BIGINT NOT NULL REFERENCES inventory_counts(id) ON DELETE CASCADE,
    product_id         BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    expected_qty       NUMERIC(18,6) NOT NULL DEFAULT 0,   -- from v_stock_levels at start
    counted_qty        NUMERIC(18,6),                       -- NULL until counted
    difference         NUMERIC(18,6) NOT NULL DEFAULT 0,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_count_line UNIQUE (inventory_count_id, product_id)
);

CREATE INDEX IF NOT EXISTS idx_inventory_count_lines_count ON inventory_count_lines (inventory_count_id);

-- ----------------------------------------------------------------------------
-- reorder_rules: min/max per (product, warehouse) driving the reorder job.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reorder_rules (
    id              BIGSERIAL PRIMARY KEY,
    product_id      BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    warehouse_id    BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE CASCADE,
    min_quantity    NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (min_quantity >= 0),
    max_quantity    NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (max_quantity >= 0),
    reorder_quantity NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (reorder_quantity >= 0),
    lead_time_days  SMALLINT NOT NULL DEFAULT 0 CHECK (lead_time_days >= 0),
    is_active       BOOLEAN  NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,
    CONSTRAINT uq_reorder_rule UNIQUE (product_id, warehouse_id),
    CONSTRAINT chk_reorder_min_max CHECK (max_quantity = 0 OR min_quantity <= max_quantity)
);

CREATE INDEX IF NOT EXISTS idx_reorder_rules_warehouse ON reorder_rules (warehouse_id);
