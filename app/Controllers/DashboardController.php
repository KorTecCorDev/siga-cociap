<?php

namespace App\Controllers;

use Core\Session;

/**
 * DashboardController
 * Punto de entrada post-login. Redirige al panel de cada rol.
 */
class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $rol = Session::user()['rol_codigo'] ?? '';

        // Cada rol tiene su propio panel — el dashboard genérico es para
        // admin, registro académico y las secretarías (que ven sus cards).
        $rolesAdmin = [
            'admin', 'registro_academico',
            'secretaria_academica', 'secretaria_administrativa',
        ];

        if (in_array($rol, $rolesAdmin)) {
            $this->view('dashboard/index', [
                'titulo' => 'Panel de administración',
            ]);
            return;
        }

        // Los demás roles van directo a su módulo
        $destinos = [
            'director_general' => url('director/anios'),
            'director_ebr'     => url('director/anios'),
            'docente'          => url('docente/inicio'),
            'padre'            => url('padre/inicio'),
        ];

        redirect($destinos[$rol] ?? url('login'));
    }
}
