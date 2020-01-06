<?php


namespace mywishlist\controllers;

use mywishlist\utils\Authentication;
use mywishlist\utils\Gravatar;
use mywishlist\utils\Selection;
use mywishlist\views\GlobalView;
use mywishlist\views\UserView;
use mywishlist\models\User;

/**
 * Class UserController, Elle a pour but de géré tous les action fait par les utilisateurs.
 * @package mywishlist\controllers
 */
class UserController
{

    /**
     * Fonction appelle lord de la deconection de l'utlisateurs
     */
    public function logout()
    {
        session_destroy();
        $render = new UserView(null, Selection::LOGOUT);
        $render->render();
    }

    /**
     * Fonction appelle apres que l'utilisateurs et click sur le boutton 'Créer mon compte', elle a donc pour but de créer le compte de l'utilisateurs.
     */
    public function registerPost()
    {
        if (isset($_POST['submit'])) if ($_POST['submit'] == 'doRegister') {

            if (!isset($_POST['terms-of-use'])) {
                $v = new UserView(null, Selection::REGISTER_TERMS_OF_USE_NOT_CHECK);
                $v->render();
                return;
            }
            if ($_POST['terms-of-use'] != 'iAgree') {
                $v = new UserView(null, Selection::REGISTER_TERMS_OF_USE_NOT_CHECK);
                $v->render();
                return;
            }

            $user_data = array();

            // check du nom d'utilisateur
            if (isset($_POST['username'])) {
                $val = filter_var($_POST['username'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['username'] = $val;
                } else{
                    self::postFailed();
                    return;
                }
            } else {
                self::postFailed();
                return;
            }

            // check de l'email
            if (isset($_POST['email'])) {
                $val = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                if ($val != FALSE) {
                    $user_data['email'] = $val;
                } else{
                    self::postFailed();
                    return;
                }
            } else {
                self::postFailed();
                return;
            }

            // check de l'email de verification
            if (isset($_POST['email-confirm'])) {

                $val = filter_var($_POST['email-confirm'], FILTER_VALIDATE_EMAIL);
                if ($val != FALSE) {
                    $user_data['email-confirm'] = $val;
                } else{
                    self::postFailed();
                    return;
                }
            } else {
                self::postFailed();
                return;
            }

            // Check si les email identique
            if ($user_data['email'] != $user_data['email-confirm']) {
                self::postFailedEmail();
                return;
            }


            // check du mot de passe
            if (isset($_POST['password'])) {

                $val = filter_var($_POST['password'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['password'] = $val;
                } else{
                    self::postFailed();
                    return;
                }
            } else {
                self::postFailed();
                return;
            }

            // check du mot de passe de verification
            if (isset($_POST['password-confirm'])) {

                $val = filter_var($_POST['password-confirm'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['password-confirm'] = $val;
                } else{
                    self::postFailed();
                    return;
                }
            } else {
                self::postFailed();
                return;
            }

            // Check si les mot de passe sont identique
            if ($user_data['password'] != $user_data['password-confirm']) {
                self::postFailedPass();
                return;
            }


            if (self::checkIfEmailExsite($user_data['email']) && self::checkIfUsernameExsite($user_data['username'])) {
                $user = new User();
                $user->email = $user_data['email'];
                $user->username = $user_data['username'];
                $user->password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
                $user->user_level = 1;
                $user_id = self::getNewUserId();
                $user->user_id = $user_id;
                $user->save();
                self::createSession($user_id);
            } else {
                self::postFailedUserOrEmailExsite();
                return;
            }

            $v = new UserView(null, Selection::REGISTER_POST_SUCCESS);
            $v->render();
            return;
        } else {
            GlobalView::bad_request();
        }
    }

    /**
     * Fonction appelle apres que l'utilisateurs et click sur le boutton 'Se connecter', elle a donc pour but de connecté l'utilisateurs.
     */
    public function loginPost()
    {
        if (isset($_POST['submit'])) if ($_POST['submit'] == 'doLogin') {
            $user_data = array();
            // check du nom d'utilisateur
            if (isset($_POST['username'])) {
                $val = filter_var($_POST['username'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['username'] = $val;
                } else {
                    $render = new UserView(null, Selection::LOGIN_POST_FAILED);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::LOGIN_POST_FAILED);
                $render->render();
                return;
            }
            // check du mot de passe
            if (isset($_POST['password'])) {
                $val = filter_var($_POST['password'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['password'] = $val;
                } else{
                    $render = new UserView(null, Selection::LOGIN_POST_FAILED);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::LOGIN_POST_FAILED);
                $render->render();
                return;
            }
            if (!self::checkIfUsernameExsite($user_data['username'])) {
                if (password_verify($user_data['password'], User::select('password_hash')->where('username', '=', $user_data['username'])->first()->password_hash)) {
                    $user_id = User::select('user_id')->where('username', '=', $user_data['username'])->get()[0]['user_id'];
                    self::createSession($user_id);
                    $render = new UserView(null, Selection::LOGIN_POST_SUCCESS);
                    $render->render();
                } else {
                    $render = new UserView(null, Selection::LOGIN_POST_USERPASS_WRONG);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::LOGIN_POST_USERPASS_WRONG);
                $render->render();
                return;
            }
        } else {
            GlobalView::bad_request();
        }
    }

    /**
     * Fonction appelle apres que l'utilisateurs et click sur le boutton qui et lié a change sont mots de passe, elle a donc pour but de changé le mot de passe de l'utilisateurs.
     */
    public function changePassword()
    {
        if (isset($_POST['submit'])) if ($_POST['submit'] == 'doChange') {
            $user_data = array();
            // check du mot de passe
            if (isset($_POST['password'])) {
                $val = filter_var($_POST['password'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['password'] = $val;
                } else{
                    $render = new UserView(null, Selection::CHANGE_FAILD);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::CHANGE_FAILD);
                $render->render();
                return;
            }
            // check du mot de passe de verification
            if (isset($_POST['password-confirm'])) {
                $val = filter_var($_POST['password-confirm'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['password-confirm'] = $val;
                } else{
                    $render = new UserView(null, Selection::CHANGE_FAILD);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::CHANGE_FAILD);
                $render->render();
                return;
            }
            // check du mot de passe exsitant
            if (isset($_POST['password-old'])) {
                $val = filter_var($_POST['password-old'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['password-old'] = $val;
                } else{
                    $render = new UserView(null, Selection::CHANGE_FAILD);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::CHANGE_FAILD);
                $render->render();
                return;
            }
            // Check si les mot de passe sont identique
            if ($user_data['password'] != $user_data['password-confirm']) {
                $render = new UserView(null, Selection::CHANGE_BAD_PASSWORD);
                $render->render();
                return;
            }
            if (!self::checkIfUsernameExsite(Authentication::getUsername())) {
                if (password_verify($user_data['password-old'], User::select('password_hash')->where('user_id', '=', Authentication::getUserId())->first()->password_hash)) {
                    $user = User::find(Authentication::getUserId());
                    $user->password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
                    $user->save();
                    session_destroy();
                    $render = new UserView(null, Selection::CHANGE_OK);
                    $render->render();
                } else {
                    $render = new UserView(null, Selection::CHANGE_BAD_PASSWORD);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::CHANGE_USER_ERROR);
                $render->render();
                return;
            }
        } else {
            GlobalView::bad_request();
        }
    }

    /**
     * Fonction appelle apres que l'utilisateurs et click sur le boutton qui et lié a change sont email, elle a donc pour but de changé l'email de l'utilisateurs.
     */
    public function accountEmail()
    {
        if (isset($_POST['submit'])) if ($_POST['submit'] == 'doEmailChange') {


            // check du mot de passe
            if (isset($_POST['password'])) {
                $val = filter_var($_POST['password'], FILTER_DEFAULT);
                if ($val != FALSE) {
                    $user_data['password'] = $val;
                } else{
                    $render = new UserView(null, Selection::CHANGE_FAILD);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::CHANGE_FAILD);
                $render->render();
                return;
            }

            if (!self::checkIfUsernameExsite(Authentication::getUsername())) {
                if (password_verify($user_data['password'], User::select('password_hash')->where('user_id', '=', Authentication::getUserId())->first()->password_hash)) {
                    // check de l'email
                    if (isset($_POST['new-email'])) {
                        $val = filter_var($_POST['new-email'], FILTER_VALIDATE_EMAIL);
                        if ($val != FALSE) {
                            $user_data['email'] = $val;
                        } else{
                            $render = new UserView(null, Selection::CHANGE_EMAIL_ERROR);
                            $render->render();
                            return;
                        }
                    } else {
                        $render = new UserView(null, Selection::CHANGE_EMAIL_ERROR);
                        $render->render();
                        return;
                    }
                    $user = User::find(Authentication::getUserId());
                    $user->email = $user_data['email'];
                    $user->save();
                    session_destroy();
                    $render = new UserView(null, Selection::CHANGE_OK);
                    $render->render();
                    return;
                } else {
                    $render = new UserView(null, Selection::CHANGE_BAD_PASSWORD);
                    $render->render();
                    return;
                }
            } else {
                $render = new UserView(null, Selection::CHANGE_USER_ERROR);
                $render->render();
                return;
            }
        } else {
            GlobalView::bad_request();
        }
    }

    /**
     * Fonction qui ajoute l'utilisateurs dans la session.
     * @param $user_id string Id de l'utilisateurs.
     */
    private static function createSession($user_id) {
        $_SESSION['user_id'] = $user_id;
    }

    /**
     * Fonction qui generais un nouvelle id d'utilisateurs.
     * @return int id d'utilisateurs generais.
     */
    private static function getNewUserId()
    {
        return User::max('user_id') + 1;
    }

    /**
     * Fonction que regarde si un email et deja present dans la base de donnée.
     * @param $email string email a verifiers.
     * @return bool vrai si l'email et deja presente, faux si elle ne l'ais pas.
     */
    private static function checkIfEmailExsite($email)
    {
        $value = User::where('email', '=', $email)->get();
        if (isset($value)) {
            return count($value) == 0;
        } else {
            return true;
        }
    }

    /**
     * Fonction que regarde si un nom d'utilisateurs et deja present dans la base de donnée.
     * @param $username string nom d'utilisateurs  a verifiers.
     * @return bool vrai si le nom d'utilisateurs et deja presente, faux si elle ne l'ais pas.
     */
    private static function checkIfUsernameExsite($username)
    {
        $value = User::where('username', '=', $username)->get();
        if (isset($value)) {
            return count($value) == 0;
        } else {
            return true;
        }
    }

    private static function postFailed()
    {
        $v = new UserView(null, Selection::REGISTER_POST_FAILED);
        $v->render();
    }

    private static function postFailedEmail()
    {
        $v = new UserView(null, Selection::REGISTER_POST_FAILED);
        $v->render();
    }

    private static function postFailedPass()
    {
        $v = new UserView(null, Selection::REGISTER_POST_FAILED);
        $v->render();
    }

    private static function postFailedUserOrEmailExsite()
    {
        $v = new UserView(null, Selection::REGISTER_POST_USER_OR_EMAIL_EXSITE);
        $v->render();
    }

    public function account()
    {
        $v = new UserView(null, Selection::ACCOUNT);
        $v->render();
    }

    /**
     * Fonction appelle quand l'utilisateurs veux supprimé sont compte.
     */
    // TODO: Ajouts la supprestion de tous les donnée lié a cette utilasteurs ou passé le comptes dans un status de deleted.
    public function accountDelete()
    {
        if (isset($_POST['submit'])) if ($_POST['submit'] == 'doDelete') {
            User::where('user_id', '=' , Authentication::getUserId())->delete();
            $v = new UserView(null, Selection::ACCOUNT_DELETE);
            $v->render();
        } else {
            GlobalView::bad_request();
        }
    }

    /**
     * Fonction appelle quand l'utilisateurs veux regardé et/ou modifier sont compte.
     */
    public function accountEdit()
    {
        $user_id = Authentication::getUserId();
        if ($user_id != Authentication::ANONYMOUS) {
            $username = Authentication::getUsername();
            $email = User::select('email')->where('username', '=', $username)->first()->email;
            $v = new UserView(array('username' => $username, 'email' => $email, 'gravatar' => Gravatar::getGravatar($email)), Selection::CHANGE_USER);
            $v->render();
        } else {
            GlobalView::unauthorized();
        }
    }
}