function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $name = $body['name'] ?? '';
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $role = $body['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'E-Mail und Passwort erforderlich.'], 400);
        }

        $qb = \App\Application\Database::$db->createQueryBuilder();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        \App\Application\Database::$db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        return $this->jsonResponse($response, ['success' => true, 'message' => 'Benutzer erfolgreich erstellt.']);
    }