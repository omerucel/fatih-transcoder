<?php

namespace Model;


class SessionMapper extends BaseMapper
{
    public function createSession($userId)
    {
        // Anahtar üretiliyor.
        $token = hash('sha256', time() . rand(0, 100000000) . $userId);

        // Veritabanına ekleniyor.
        $statement = $this->pdo->prepare('INSERT INTO session(user_id, token) VALUES (:user_id, :token)');
        $statement->execute(array(
            ':user_id' => $userId,
            ':token' => $token
        ));

        return $token;
    }

    public function getUser($token)
    {
        $statement = $this->pdo->prepare('SELECT user.* FROM user INNER JOIN session ON session.user_id = user.id WHERE session.token=:token');
        $statement->execute(array(
            ':token' => $token
        ));

        return $statement->fetch(\PDO::FETCH_OBJ);
    }

    public function verifyToken($token)
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) AS count FROM session WHERE token = :token');
        $statement->execute(array(
            ':token' => $token
        ));

        return $statement->fetch(\PDO::FETCH_OBJ)->count == 1 ? true : false;
    }

    public function isAdmin($token)
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) AS count FROM session, user '
            . 'WHERE session.token = :token AND session.user_id = user.id AND user.is_admin = 1');
        $statement->execute(array(
            ':token' => $token
        ));

        return $statement->fetch(\PDO::FETCH_OBJ)->count == 1 ? true : false;
    }

    public function deleteToken($token)
    {
        $statement = $this->pdo->prepare('DELETE FROM session WHERE token = :token');
        $statement->execute(array(
            ':token' => $token
        ));
    }
}
