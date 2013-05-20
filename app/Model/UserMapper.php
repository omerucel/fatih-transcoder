<?php

namespace Model;

class UserMapper extends BaseMapper
{

    /**
     * @param $email
     * @param $password
     * @return string
     */
    public function createUser($email, $password)
    {
        // Parola tuzlanıyor.
        $salt = hash('sha256', time() . md5($email) . md5($password));
        $password = hash('sha256', $salt . $password);

        // Kullanıcı ekleniyor.
        $statement = $this->pdo->prepare('INSERT INTO user(email, salt, password) VALUES (:email, :salt, :password)');
        $statement->execute(array(
            ':email' => $email,
            ':salt' => $salt,
            ':password' => $password
        ));

        return $this->pdo->lastInsertId('user');
    }

    public function saveUser($id, $email, $password, $password_repeat)
    {
        $salt = null;
        if ($password_repeat != null && $password != null)
        {
            // Parola tuzlanıyor.
            $salt = hash('sha256', time() . md5($email) . md5($password));
            $password = hash('sha256', $salt . $password);
        }

        if ($salt == null)
        {
            $statement = $this->pdo->prepare('UPDATE user SET email = :email WHERE id = :id');
            $statement->execute(array(
                ':email' => $email,
                ':id' => $id
            ));
        }else{
            $statement = $this->pdo->prepare('UPDATE user SET email = :email, password = :password, salt = :salt  WHERE id = :id');
            $statement->execute(array(
                ':email' => $email,
                ':id' => $id,
                ':salt' => $salt,
                ':password' => $password
            ));
        }
    }

    /**
     * @param $email
     * @return bool
     */
    public function checkEmail($email)
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM user WHERE email =:email');
        $statement->execute(array(
            ':email' => $email
        ));

        return $statement->fetchColumn(0) > 0;
    }

    public function checkUser($email, $password)
    {
        $statement = $this->pdo->prepare('SELECT id, salt, password FROM user WHERE email =:email');
        $statement->execute(array(
            ':email' => $email
        ));

        $user = $statement->fetch(\PDO::FETCH_OBJ);
        if ($user == null)
            return false;

        if (hash('sha256', $user->salt . $password) === $user->password)
            return $user->id;
    }
}
