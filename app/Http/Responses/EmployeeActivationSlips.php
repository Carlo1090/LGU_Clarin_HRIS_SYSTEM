<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EmployeeActivationSlips
{
    /**
     * @param  list<array{employee_number: string, name: string, department: string, activation_code: string, expires_at: string, issuance_id: int}>  $slips
     * @param  list<array{row: int, errors: list<string>}>  $rejected
     */
    public static function respond(Request $request, array $slips, array $rejected = [], bool $imported = false, bool $reissued = false): Response
    {
        $response = Inertia::render('hris/employee-onboarding-result', [
            'imported' => $imported,
            'created' => $reissued ? 0 : count($slips),
            'reissued' => $reissued,
            'rejected' => $rejected,
            'activationUrl' => url('/activate'),
        ])->flash('activationSlips', $slips)->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
