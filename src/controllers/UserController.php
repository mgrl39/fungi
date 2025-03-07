<?php

namespace App\Controllers;

use PDO;
use App\Config\ErrorMessages;
use App\Repositories\UserRepository;

class UserController
{
    private $pdo;
    private $repository;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->repository = new UserRepository($pdo);
    }
    
    public function handleRequest($method, $endpoint)
    {
        // Implementación similar a FungiController
    }
    
    // Métodos específicos para manejar usuarios
} 