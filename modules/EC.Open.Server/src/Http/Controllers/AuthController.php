<?php

/*
 * This file is part of ibrand/EC-Open-Server.
 *
 * (c) 果酱社区 <https://guojiang.club>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GuoJiangClub\EC\Open\Server\Http\Controllers;

use GuoJiangClub\Component\User\Repository\UserBindRepository;
use GuoJiangClub\Component\User\Repository\UserRepository;
use GuoJiangClub\EC\Open\Core\Auth\User;
use GuoJiangClub\Component\User\UserService;
use iBrand\Sms\Facade as Sms;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    protected $userRepository;
    protected $userBindRepository;
    protected $userService;

    public function __construct(UserRepository $userRepository, UserBindRepository $userBindRepository
        , UserService $userService)
    {
        $this->userRepository = $userRepository;
        $this->userBindRepository = $userBindRepository;
        $this->userService = $userService;
    }

    public function smsLogin()
    {
        $mobile = request('mobile');
        $code = request('code');

        if (!Sms::checkCode($mobile, $code)) {
            return $this->failed('验证码错误');
        }

        $is_new = false;

        if (!$user = $this->userRepository->getUserByCredentials(['mobile' => $mobile])) {
            $user = $this->userRepository->create(['mobile' => $mobile]);
            $is_new = true;
        }
        Log::debug(["is_new" => $is_new]);
        if (User::STATUS_FORBIDDEN == $user->status) {
            return $this->failed('您的账号已被禁用，联系网站管理员或客服！');
        }

        //1. create user token.
        Log::debug(["111111"]);
        $token = $user->createToken($mobile)->accessToken;
        Log::debug(["222222"]);
        Log::debug(["token" => $token]);
        //2. bind user bind data to user.
        $this->userService->bindPlatform($user->id, request('open_id'), config('ibrand.wechat.mini_program.default.app_id'), 'miniprogram');

        return $this->success(['token_type' => 'Bearer', 'access_token' => $token, 'is_new_user' => $is_new]);
    }
}
