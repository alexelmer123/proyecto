<?php
declare(strict_types=1);

final class AuthController extends Controller
{
    public function index(): void
    {
        $this->redirect('/auth/login');
    }

    public function login(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/producto/index');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim((string) ($_POST['email']    ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                $error = 'Ingresa tu correo y contraseña.';
            } else {
                $usuarioModel = new Usuario();
                $usuario = $usuarioModel->findByEmail($email);

                if ($usuario === null || !password_verify($password, (string) $usuario['password'])) {
                    $error = 'Credenciales incorrectas.';
                    (new Auditoria())->registrar('login_fallido', 'auth', null, "Intento fallido para «{$email}».");
                } else {
                    session_regenerate_id(true);
                    $_SESSION['usuario'] = [
                        'id'     => (int) $usuario['id'],
                        'nombre' => (string) $usuario['nombre'],
                        'email'  => (string) $usuario['email'],
                        'rol'    => (string) $usuario['rol'],
                    ];
                    $usuarioModel->actualizarUltimoAcceso((int) $usuario['id']);
                    $this->audit('login', 'auth', (string) $usuario['id'], "Inicio de sesión de {$usuario['nombre']}.");
                    $this->setFlash('success', 'Bienvenido, ' . (string) $usuario['nombre'] . '.');
                    $this->redirect('/dashboard/index');
                }
            }
        }

        $this->render('auth/login', ['error' => $error], withLayout: false);
    }

    public function logout(): void
    {
        $uid = $_SESSION['usuario']['id'] ?? null;
        if ($uid !== null) {
            $this->audit('logout', 'auth', (string) $uid, 'Cierre de sesión.');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        session_start();
        $this->setFlash('success', 'Sesión cerrada correctamente.');
        $this->redirect('/auth/login');
    }
}
