<?php

namespace FSEdit;

use FSEdit\Exception\BadRequestException;
use FSEdit\Exception\ConflictException;
use FSEdit\Exception\ForbiddenException;
use FSEdit\Exception\NotFoundException;
use FSEdit\Model\User;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController extends Controller
{
    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function login($req, $res)
    {
        $email = $this->requireParam($req, 'email');
        $password = $this->requireParam($req, 'password');

        try {
            $user = $this->User()->loadByEmail($email);
        } catch (NotFoundException $e) {
            throw new ForbiddenException();
        }

        if (!$user->comparePasswords($password)) {
            throw new ForbiddenException();
        }

        $token = $this->createSession($user);
        return $this->json($res, [
            'email' => $user->getEmail(),
            'token' => $token
        ]);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function register($req, $res)
    {
        $email = trim($this->requireParam($req, 'email'));
        $password = $this->requireParam($req, 'password');

        if (!preg_match('/^[!-?A-~]+@[!-?A-~]+$/', $email) || strlen($email) > 64) {
            throw new BadRequestException('invalid email');
        }

        try {
            $this->User()->loadByEmail($email);
            throw new ConflictException('user already registered');
        } catch (NotFoundException $_) {
        }

        //todo validate email
        //todo validate captcha

        $user = $this->User();
        $user->register($email, $password);

        $token = $this->createSession($user);
        return $this->json($res, [
            'email' => $user->getEmail(),
            'token' => $token
        ]);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function logout($req, $res)
    {
        $user = $this->requireUser();

        $this->database->delete('sessions', [
            'user_id' => $user->getId(),
            'token' => $user->getToken(),
            'LIMIT' => 1
        ]);

        return $this->json($res, []);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function exists($req, $res)
    {
        $email = trim($this->requireParam($req, 'email'));

        if (!preg_match('/^[!-?A-~]+@[!-?A-~]+$/', $email) || strlen($email) > 64) {
            throw new BadRequestException('invalid email');
        }

        try {
            $this->User()->loadByEmail($email);
            throw new ConflictException('user already registered');
        } catch (NotFoundException $_) {
        }

        return $this->json($res, []); //status 200
    }

    /**
     * @param User $user
     * @return string
     */
    protected function createSession($user)
    {
        $token = Utils::randomStr(64);

        $this->database->insert('sessions', [
            'user_id' => $user->getId(),
            'token' => $token
        ]);

        return $token;
    }
}