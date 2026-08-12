<?php

declare(strict_types=1);

namespace App\Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class RequestBase extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}