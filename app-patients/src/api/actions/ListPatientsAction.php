<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\infra\adapters\ApiResponseBuilder;

final class ListPatientsAction
{
    public function __construct(private ServicePatientInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('authenticated_user');
        if ($user === null) {
            return ApiResponseBuilder::create()->status(401)->error('Unauthorized')->build($response);
        }

        $params = $request->getQueryParams();
        $email = array_key_exists('email', $params) ? trim((string)$params['email']) : null;
        if ($email === '') {
            $email = null;
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponseBuilder::create()->status(400)->error('email invalide')->build($response);
        }

        if ($user->role === Roles::PRATICIEN) {
            if ($email !== null) {
                $patient = $this->service->findPatientByEmail($email);
                $patients = $patient ? [$patient] : [];
            } else {
                $patients = $this->service->listPatients();
            }
        } elseif ($user->role === Roles::PATIENT) {
            $patient = $this->service->getPatientById($user->ID);
            $patients = $patient ? [$patient] : [];
        } else {
            return ApiResponseBuilder::create()->status(403)->error('Forbidden: insufficient permissions')->build($response);
        }

        $data = array_map(static function ($dto) {
            $attributes = $dto->jsonSerialize();
            $id = (string)($attributes['id'] ?? '');
            unset($attributes['id']);
            $links = [
                'self' => ['href' => '/api/patients/' . $id],
            ];
            return ApiResponseBuilder::resource('patients', $id, $attributes, $links);
        }, $patients);

        $self = '/api/patients';
        if ($email !== null && $user->role === Roles::PRATICIEN) {
            $self .= '?' . http_build_query(['email' => $email]);
        }

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($data)
            ->links(['self' => ['href' => $self]])
            ->build($response);
    }
}
