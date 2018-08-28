<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('/vcalendar/import[?event=]','ApiOld\Calendar\EventImportController@index');
$app->get('/rest/vcalendar/import[?event=]','ApiOld\Calendar\EventImportController@index');

$app->get('/ExpressoVersion', 'ApiOld\Core\ExpressoVersionController@index');
$app->post('/ExpressoVersion', 'ApiOld\Core\ExpressoVersionController@index');
$app->post('/Login', 'ApiOld\Core\LoginController@index');
$app->post('/UserApps','ApiOld\Core\UserAppsController@index');
$app->post('/UserApi','ApiOld\Core\UserApiController@index');

$app->group( ['middleware' => ['auth'] ], function () use ($app) {

	$app->post('/Logout', 'ApiOld\Core\LogoutController@index');

	$app->post('/Mail/Folders', 'ApiOld\Mail\FoldersController@index');
	$app->post('/Mail/RenameFolder', 'ApiOld\Mail\RenameFolderController@index');
	$app->post('/Mail/AddFolder', 'ApiOld\Mail\AddFolderController@index');
	$app->post('/Mail/DelFolder', 'ApiOld\Mail\DelFolderController@index');
	$app->post('/Mail/FlagMessage', 'ApiOld\Mail\FlagMessageController@index');
	$app->post('/Mail/DelMessage', 'ApiOld\Mail\DelMessageController@index');
	$app->post('/Mail/Messages', 'ApiOld\Mail\MessagesController@index');
	$app->post('/Mail/MoveMessages', 'ApiOld\Mail\MoveMessagesController@index');
	$app->post('/Mail/CleanTrash', 'ApiOld\Mail\CleanTrashController@index');
	$app->post('/Mail/Send', 'ApiOld\Mail\SendController@index');
	$app->post('/Mail/SpamMessage', 'ApiOld\Mail\SpamMessageController@index');
	$app->post('/Mail/SendSupportFeedback', 'ApiOld\Mail\SendSupportFeedbackController@index');
	$app->post('/Mail/Attachment','ApiOld\Mail\AttachmentController@index');

	$app->post('/Preferences/ChangePassword', 'ApiOld\Preferences\ChangePasswordController@index');
	$app->post('/Preferences/UserPreferences', 'ApiOld\Preferences\UserPreferencesController@index');
	$app->post('/Preferences/ChangeUserPreferences', 'ApiOld\Preferences\ChangeUserPreferencesController@index');

	$app->post('/Calendar/Events', 'ApiOld\Calendar\EventsController@index');
	$app->post('/Calendar/Event', 'ApiOld\Calendar\EventController@index');
	$app->post('/Calendar/AddEvent', 'ApiOld\Calendar\AddEventController@index');
	$app->post('/Calendar/DelEvent', 'ApiOld\Calendar\DelEventController@index');
	$app->post('/Calendar/EventCategories', 'ApiOld\Calendar\EventCategoriesController@index');

	$app->post('/Services/Chat', 'ApiOld\Services\ChatController@index');

	$app->post('/Catalog/Contacts', 'ApiOld\Catalog\ContactsController@index');
	$app->post('/Catalog/ContactAdd', 'ApiOld\Catalog\ContactAddController@index');
	$app->post('/Catalog/ContactDelete', 'ApiOld\Catalog\ContactDeleteController@index');
	$app->post('/Catalog/ContactPicture', 'ApiOld\Catalog\ContactPictureController@index');
	$app->post('/Catalog/Photo', 'ApiOld\Catalog\PhotoController@index');
	$app->get('/Catalog/Photo', 'ApiOld\Catalog\PhotoController@index');

	$app->post('/Admin/CreateUser', 'ApiOld\Admin\CreateUserController@index');
	$app->post('/Admin/DeleteUser', 'ApiOld\Admin\DeleteUserController@index');
	$app->post('/Admin/GetUsers', 'ApiOld\Admin\GetUsersController@index');
	$app->post('/Admin/SearchLdap', 'ApiOld\Admin\SearchLdapController@index');
	$app->post('/Admin/RenameUser', 'ApiOld\Admin\RenameUserController@index');
	$app->post('/Admin/UpdateUser', 'ApiOld\Admin\UpdateUserController@index');
});
