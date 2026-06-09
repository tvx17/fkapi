public function register(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $role = $body['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'E-Mail und Passwort erforderlich.'], 400);
        }

        $qb = \App\Application\Database::$db->createQueryBuilder();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        \App\Application\Database::$db->insert('users', [
            'name' => $email,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        \App\Application\Logger::$logger->info("Neuer Benutzer registriert: {email} (ID: {id})", ['email' => $email, 'id' => \App\Application\Database::$db->lastInsertId()]);

        return $this->jsonResponse($response, ['success' => true, 'message' => 'Benutzer erfolgreich registriert.']);
    }