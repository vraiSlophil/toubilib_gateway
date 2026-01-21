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

        if ($user->role === Roles::PRATICIEN) {
            $patients = $this->service->listPatients();
        } elseif ($user->role === Roles::PATIENT) {
            $patient = $this->service->getPatientById($user->ID);
            $patients = $patient ? [$patient] : [];
        } else {
            return ApiResponseBuilder::create()->status(403)->error('Forbidden: insufficient permissions')->build($response);
        }

        $data = array_map(static function ($dto) {
            $item = $dto->jsonSerialize();
            $item['_links'] = [
                'self' => ['href' => '/api/patients/' . $item['id']],
            ];
            return $item;
        }, $patients);

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($data)
            ->links(['self' => ['href' => '/api/patients']])
            ->build($response);
    }
}
