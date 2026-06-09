 <?php

namespace App\Controllers\Api\V1\Users;

class GetRoleUsersController
{    
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface  
    {
        $user = $request->getAttribute('token_user');
        if (!$user) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Benutzerinformationen nicht gefunden.'], 404);
        }

        return $this->jsonResponse($response, [
            'success' => true,
            'role' => $user['role']
        ]);
    }
}