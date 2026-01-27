<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ApplicationRepository;
use App\Service\ImageService;
use App\Validators\ApplicationValidator;

class ModpackController
{
    private ApplicationRepository $appRepo;
    private ImageService $imageService;
    private ApplicationValidator $validator;

    public function __construct()
    {
        $this->appRepo = new ApplicationRepository();
        $this->imageService = new ImageService();
        $this->validator = new ApplicationValidator();
    }

    public function apply(Request $request): void
    {
        // Это алиас для ApplicationController::create
        $applicationController = new ApplicationController();
        $applicationController->create($request);
    }
}
