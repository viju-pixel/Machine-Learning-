<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Http\Request;
use App\Interfaces\AuthUserRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{

    private $authUserRepository;

    public function __construct(
        AuthUserRepositoryInterface $authUserRepository,
    ) 
    {
        $this->authUserRepository = $authUserRepository;
    }
    /**
     * If the user is logged in, redirect to the dashboard, otherwise show the login page.
     *
     * @param Request request The request object.
     *
     * @return A view
     */
    public function login(Request $request)
    {
        if (auth()->check()) {
            return redirect()->to('/admin/dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * It checks if the user is logged in, if not, it redirects to the login page.
     *
     * @param Request request The request object.
     */
    public function checkLoginPost(Request $request)
    {
        $rule = [
            'email' => 'required|email',
            'password' => 'required',
        ];
        $messages = [
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid Email address',
            'password.required' => 'Password is required',
        ];

        $validation = Validator::make($request->all(), $rule, $messages);
        if ($validation->fails()) {
            return redirect()->back()->withInput()->withErrors($validation);
        }

        $cred = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        auth()->attempt($cred, true);

        if (auth()->check()) {
            $auth_user_id = auth()->user()->id;

            return redirect()->to('/admin/dashboard');
        } else {
            $message = 'email or password is incorrect';

            return redirect()->back()->with('error', $message)->withInput();
        }
    }

    /**
     * It returns the view of the forget password page.
     *
     * @return HTML view
     */
    public function getForgetPassword()
    {
        return view('admin.auth.forgetPassword');
    }

    /**
     * It takes the email from the user, generates a random token, inserts the email and token into the
     * password_resets table, and sends an email to the user with the token.
     *
     * #TODO code need to be filtered and Requests need to be created
     *
     * @param Request request This is the request object that contains the email address of the user.
     *
     * @return text success message
     */
    public function processForgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);
        $token = Str::random(64);

        DB::table('password_resets')->insert(
            ['email' => $request->email, 'token' => $token, 'created_at' => now()]
        );
        //TODO mail send on user submission of reset password link

        return back()->with('message', 'We have e-mailed your password reset link!');
    }

    /**
     * It returns a view called `resetPassword` from the `admin.auth` folder.
     *
     * @param Request request The request object.
     * @param token The token that was sent to the user's email address.
     *
     * @return A view
     */
    public function getResetPassword(Request $request, $token)
    {
        return view('admin.auth.resetPassword', ['token' => $token]);
    }

    /**
     * It checks if the email and token are valid, if they are, it updates the password and deletes the
     * token from the database.
     *
     * #TODO code need to be filtered and Requests need to be created
     *
     * @param Request request The request object.
     *
     * @return the view of the reset password form.
     */
    public function processResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|string|confirmed',
            'password_confirmation' => 'required',
        ]);

        $updatePassword = DB::table('password_resets')
                            ->where(['email' => $request->email, 'token' => $request->token])
                            ->first();

        if (!$updatePassword) {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        User::where('email', $request->email)->update(['password' => bcrypt($request->password)]);

        DB::table('password_resets')->where(['email'=> $request->email])->delete();

        return redirect('/admin/login')->with('message', 'Your password has been changed!');
    }

    /**
     * It returns a view called `profile` from the `admin.settings` folder.
     *
     * @param Request request The request object.
     *
     * @return the view of the user profile form.
     */
    public function getProfile($id)
    {
        $user=$this->authUserRepository->getAuthUserById($id);

        return view('admin.settings.profile', compact('user'));
    }

    /**
     * It returns a view called `profile` from the `admin.settings` folder.
     *
     * @param Request request The request object.
     *
     * @return the view of the user profile form .
     */
   
    public function updateProfile(Request $request,$id)
    {
    $authUserDetails = $request->only([
        'first_name',
        'last_name',
        'email',
        'phone',
    ]);

    $updatedAuthUser = $this->authUserRepository->updateAuthUser($id, $authUserDetails);

    /*
     * Code to upload profile picture for authuser
     */
    if ($request->hasFile('profile_pic')) {
        $image = $request->file('profile_pic');
        $imageName = uploadImages($image, '/assets/admin/uploads/admin-user');
        $updatedAuthUser->profile_pic = $imageName;
    }
    //image upload ends

    $updatedAuthUser->update();

    return back()->with('success', trans('app.update_profile'));
}

    /**
     * It returns the view of the change password page.
     *
     * @return HTML view
     */
    public function getChangePassword(Request $request)
    {
        return view('admin.settings.profile');
    }

    /**
     * It takes old password, if it is correct, it matches new password and confirm password then
     * updated old password to new password.
     *
     * @param Request request The request object.
     *
     * @return the view of the change password form.
     */
    public function updatePassword(Request $request)
    {
        // Validation
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        //Match The Old Password
        if (!Hash::check($request->old_password, auth()->user()->password)) {
            return redirect()->back()->with('error', trans('app.password_not_matched'));
        }

        //Update the new Password
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', trans('app.password_success'));
    }

    /**
     * It logs out the user and redirects to the login page.
     *
     * @param Request request The request object.
     */
    public function adminLogout(Request $request)
    {
        auth()->logout();
        session()->flush();

        return redirect('/admin/login')->with('message', 'Logout Successfully!');
    }
}
