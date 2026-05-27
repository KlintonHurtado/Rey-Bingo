<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('dashboard', 'Dashboard::index');

// app/Config/Routes.php

// Añade esta ruta al grupo de rutas de payment
$routes->group('payment', function($routes) {
    $routes->get('form', 'PaymentController::showPaymentForm'); // Nueva ruta para mostrar el formulario
    $routes->post('create', 'PaymentController::createPayment');
    $routes->get('success', 'PaymentController::success');
    $routes->get('cancel', 'PaymentController::cancel');
    $routes->get('error', 'PaymentController::error');
});

$routes->group('cron', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('check-auto-games', 'Cron::checkAutoGames');
    $routes->get('run-auto-games', 'Cron::runAutoGames');
    $routes->get('run-autoadd-games', 'Cron::runAutoAddGames');
    $routes->get('ball-sequence', 'Cron::ballSequence');
});

$routes->get('/', 'Signin::index');
$routes->get('signin', 'Signin::index');
$routes->post('signin/signinSubmit', 'Signin::signinSubmit');
$routes->get('logout', 'Signin::logout');

$routes->get('signup/google', 'Signup::google');
$routes->get('signup/signupGoogleSubmit', 'Signup::signupGoogleSubmit');
$routes->post('signup/signupStepSubmit', 'Signup::signupStepSubmit');
$routes->post('signup/signupSubmit', 'Signup::signupSubmit');
$routes->get('signup', 'Signup::index');
$routes->get('signup/(:segment)', 'Signup::index/$1');
$routes->get('verify/(:any)', 'Signup::verifyEmail/$1');

$routes->get('restore', 'Restore::index');
$routes->post('restore/restoreSubmit', 'Restore::restoreSubmit');
$routes->get('restore/(:any)', 'Restore::index/$1');
$routes->post('restore/changeSubmit', 'Restore::changeSubmit');

$routes->get('cartons', 'Cartons::index');
$routes->post('cartons/cartonsSubmit', 'Cartons::cartonsSubmit');

// Agregar estas rutas
/*$routes->post('notifications/subscribe', 'Notifications::subscribe');
$routes->post('notifications/unsubscribe', 'Notifications::unsubscribe');
$routes->get('notifications/vapid-key', 'Notifications::getVapidPublicKey');

$routes->post('notification/save-token', 'Notifications::saveToken');
$routes->post('notification/send', 'Notifications::sendNotification');*/

$routes->get('games', 'Games::index');
$routes->get('games/add', 'Games::add');
$routes->get('games/add/(:num)', 'Games::add/$1');
$routes->get('games/addmodality', 'Games::addmodality');
$routes->get('start', 'Games::start');
$routes->post('games/addgameSubmit', 'Games::addgameSubmit');
$routes->post('games/startgameSubmit', 'Games::startgameSubmit');
$routes->get('games/gamesGet', 'Games::gamesGet');
$routes->get('games/gameslistGet/(:any)/(:any)/(:any)/(:num)', 'Games::gameslistGet/$1/$2/$3/$4');
$routes->get('games/gameslistGet/(:any)/(:any)/(:any)', 'Games::gameslistGet/$1/$2/$3/1');
$routes->get('game/(:num)', 'Games::game/$1');
$routes->get('live/(:num)', 'Games::live/$1');
$routes->get('games/playersGet/(:num)', 'Games::playersGet/$1');
$routes->get('games/gameGetAccumulated', 'Games::gameGetAccumulated');
$routes->post('games/deleteGame', 'Games::deleteGame');
$routes->post('games/uploadVideo', 'Games::uploadVideo');
$routes->get('games/statisticsView', 'Games::statisticsView');
$routes->get('games/statisticsGet/(:segment)/(:segment)/(:segment)/(:segment)/(:segment)', 'Games::statisticsGet/$1/$2/$3/$4/$5');

$routes->get('playings', 'Playings::index');
$routes->get('play', 'Playings::play');
$routes->get('playing', 'Playings::playing');
$routes->post('playings/playSubmit', 'Playings::playSubmit');
$routes->get('playings/numberGet', 'Playings::numberGet');
$routes->post('playings/dialNumber', 'Playings::dialNumber');
$routes->post('playings/singBingo', 'Playings::singBingo');
$routes->get('playings/getInitialGameState', 'Playings::getInitialGameState');
$routes->post('playings/saveCartons', 'Playings::saveCartons');
$routes->post('playings/messageSubmit', 'Playings::messageSubmit');
$routes->get('playings/messageGet', 'Playings::messageGet');
$routes->post('playings/volumeSubmit', 'Playings::volumeSubmit');
$routes->post('playings/microphoneSubmit', 'Playings::microphoneSubmit');
$routes->post('playings/checkSubmit', 'Playings::checkSubmit');
$routes->post('playings/claimPrize', 'Playings::claimPrize');
$routes->get('playings/totalCartonsGet/(:num)', 'Playings::totalCartonsGet/$1');
$routes->get('playings/availableCartonsGet/(:num)', 'Playings::availableCartonsGet/$1');
$routes->get('playings/generateCartonsGet/(:num)', 'Playings::generateCartonsGet/$1');
$routes->post('playings/loadMoreCartons', 'Playings::loadMoreCartons');
$routes->post('playings/selectCarton', 'Playings::selectCarton');
$routes->post('playings/getCartonsStatus', 'Playings::getRealTimeCartonsStatus');
$routes->post('playings/getRealTimeCartonsStatus', 'Playings::getRealTimeCartonsStatus');
$routes->post('playings/deselectCarton', 'Playings::deselectCarton');
$routes->post('playings/checkExpiredCartons', 'Playings::checkExpiredCartons');
$routes->get('playings/getSelectedCartons/(:num)', 'Playings::getSelectedCartons/$1');
$routes->post('playings/playGame', 'Playings::playGame');
$routes->get('playings/cleanExpiredCartons', 'Playings::cleanExpiredCartons');

$routes->post('playings/selectCartons', 'Playings::selectCartons');
$routes->get('playings/generateCartons/(:num)', 'Playings::generateCartons/$1');

$routes->get('boards', 'Boards::index');
$routes->get('board', 'Boards::board');
$routes->get('live', 'Boards::live');
$routes->post('boards/boardSubmit', 'Boards::boardSubmit');
$routes->get('boards/numberAutoSubmit', 'Boards::numberAutoSubmit');
$routes->get('boards/numberSubmit/(:segment)', 'Boards::numberSubmit/$1');
$routes->get('boards/numberGet', 'Boards::numberGet');
$routes->get('boards/winnersGet', 'Boards::winnersGet');
$routes->get('boards/playersGet', 'Boards::playersGet');
$routes->get('boards/playersGetCount', 'Boards::playersGetCount');
$routes->get('boards/awardsGet', 'Boards::awardsGet');
$routes->get('boards/awardsGameGet', 'Boards::awardsGameGet');
$routes->get('boards/winnersListGet/(:segment)/(:segment)/(:segment)/(:segment)/(:segment)/(:num)', 'Boards::winnersListGet/$1/$2/$3/$4/$5/$6');

$routes->get('purchases', 'Purchases::index');
$routes->get('purchase', 'Purchases::purchase');
$routes->post('purchases/purchaseSubmit', 'Purchases::purchaseSubmit');

$routes->get('users', 'Users::index');
$routes->get('users/add', 'Users::add');
$routes->get('users/add/(:num)', 'Users::add/$1');
$routes->post('users/userSubmit', 'Users::userSubmit');
$routes->post('users/deleteUser', 'Users::deleteUser');
$routes->post('users/banUser', 'Users::banUser');
$routes->get('users/getUserDetails/(:num)', 'Users::getUserDetails/$1');
$routes->get('profile', 'Users::profile');
$routes->post('users/profileSubmit', 'Users::profileSubmit');
$routes->get('password', 'Users::password');
$routes->post('users/passwordSubmit', 'Users::passwordSubmit');
$routes->post('users/profileStepSubmit', 'Users::profileStepSubmit');
$routes->get('users/userNotifications', 'Users::userNotifications');
$routes->get('users/exportRequiredFields', 'Users::exportRequiredFields');
$routes->post('users/markNotificationRead', 'Users::markNotificationRead');
$routes->post('users/testNotification', 'Users::testNotification');
$routes->get('users/referralsGet', 'Users::referralsGet');
$routes->get('users/referralCode', 'Users::referralCode');

$routes->get('payments', 'Payments::index');
$routes->get('payments/depositGet', 'Payments::depositGet');
$routes->post('payments/depositStepSubmit', 'Payments::depositStepSubmit');
$routes->post('payments/depositSubmit', 'Payments::depositSubmit');
$routes->post('payments/depositPaypalSubmit', 'Payments::depositPaypalSubmit');
$routes->get('payments/retireGet', 'Payments::retireGet');
$routes->post('payments/retireSubmit', 'Payments::retireSubmit');

$routes->get('payments/paymentsGet', 'Payments::paymentsGet');
$routes->get('payments/paymentsAjax', 'Payments::paymentsAjax');
$routes->post('payments/updateStatus', 'Payments::updateStatus');
$routes->get('payments/export', 'Payments::exportData');

$routes->get('payments/infobankGet/(:num)', 'Payments::infobankGet/$1');
$routes->get('payments/retirebankGet', 'Payments::retirebankGet');
$routes->get('payments/settingswalletGet', 'Payments::settingswalletGet');
$routes->post('payments/settingswalletSubmit', 'Payments::settingswalletSubmit');
$routes->get('payments/availablewalletGet', 'Payments::availablewalletGet');
$routes->post('payments/statusSubmit', 'Payments::statusSubmit');
$routes->post('payments/payawardSubmit', 'Payments::payawardSubmit');
$routes->get('payments/transferGet', 'Payments::transferGet');
$routes->post('payments/transferSubmit', 'Payments::transferSubmit');
$routes->get('payments/transferUserGet/(:segment)', 'Payments::transferUserGet/$1');
$routes->get('payments/requestGet/(:segment)/(:num)', 'Payments::requestGet/$1/$2');
$routes->get('payments/modalVoucher/(:num)', 'Payments::modalVoucher/$1');

$routes->get('payments/show', 'Payments::show');
$routes->post('payments/webhook/(:segment)', 'PaymentWebhook::gateway/$1');

$routes->get('kyc', 'Kyc::index');
$routes->post('kyc/submit', 'Kyc::submit');
$routes->get('kycAdmin', 'KycAdmin::index');
$routes->post('kycAdmin/review/(:num)', 'KycAdmin::review/$1');

$routes->get('bingo', 'Bingo::index');
$routes->get('bingo/generate-number', 'Bingo::generateNumber');

$routes->get('home/settingsGet', 'Home::settingsGet');
$routes->post('home/settingsSubmit', 'Home::settingsSubmit');
$routes->post('home/bankSubmit', 'Home::bankSubmit');
$routes->post('home/activateAlgorithm', 'Home::activateAlgorithm');
$routes->get('home/bankGet', 'Home::bankGet');
$routes->get('home/bankGet/(:num)', 'Home::bankGet/$1');
$routes->post('home/deleteBank', 'Home::deleteBank');

// Notifications routes
$routes->post('notifications/subscribe', 'Notifications::subscribe');
$routes->post('notifications/registerToken', 'Notifications::registerToken');
$routes->post('notifications/unsubscribe', 'Notifications::unsubscribe');
$routes->get('notifications/getPublicKey', 'Notifications::getPublicKey');
$routes->get('notifications/getUserNotifications', 'Notifications::getUserNotifications');
$routes->get('notifications/markAsRead/(:num)', 'Notifications::markAsRead/$1');
$routes->get('notifications/markAllAsRead', 'Notifications::markAllAsRead');
$routes->get('notifications/getUnreadCount', 'Notifications::getUnreadCount');
$routes->get('notifications/sendTest', 'Notifications::sendTest');
$routes->get('notifications/getSubscriptionStatus', 'Notifications::getSubscriptionStatus');

// Notifications Admin routes
$routes->get('notificationsAdmin', 'NotificationsAdmin::index');
$routes->get('notificationsAdmin/create', 'NotificationsAdmin::create');
$routes->post('notificationsAdmin/create', 'NotificationsAdmin::create');
$routes->get('notificationsAdmin/view/(:num)', 'NotificationsAdmin::view/$1');
$routes->get('notificationsAdmin/sendNow/(:num)', 'NotificationsAdmin::sendNow/$1');
$routes->get('notificationsAdmin/delete/(:num)', 'NotificationsAdmin::delete/$1');
$routes->get('notificationsAdmin/templates', 'NotificationsAdmin::templates');
$routes->get('notificationsAdmin/createTemplate', 'NotificationsAdmin::createTemplate');
$routes->post('notificationsAdmin/createTemplate', 'NotificationsAdmin::createTemplate');
$routes->get('notificationsAdmin/editTemplate/(:num)', 'NotificationsAdmin::editTemplate/$1');
$routes->post('notificationsAdmin/editTemplate/(:num)', 'NotificationsAdmin::editTemplate/$1');
$routes->get('notificationsAdmin/deleteTemplate/(:num)', 'NotificationsAdmin::deleteTemplate/$1');
$routes->get('notificationsAdmin/processScheduled', 'NotificationsAdmin::processScheduled');
$routes->get('notificationsAdmin/statistics', 'NotificationsAdmin::statistics');
$routes->get('notificationsAdmin/subscriptions', 'NotificationsAdmin::subscriptions');
$routes->get('notificationsAdmin/cleanupOld', 'NotificationsAdmin::cleanupOld');

// Email Marketing routes
$routes->get('emailMarketing', 'EmailMarketing::index');
$routes->get('emailMarketing/create', 'EmailMarketing::create');
$routes->post('emailMarketing/create', 'EmailMarketing::create');
$routes->get('emailMarketing/view/(:num)', 'EmailMarketing::view/$1');
$routes->get('emailMarketing/edit/(:num)', 'EmailMarketing::edit/$1');
$routes->post('emailMarketing/edit/(:num)', 'EmailMarketing::edit/$1');
$routes->get('emailMarketing/sendTest/(:num)', 'EmailMarketing::sendTest/$1');
$routes->post('emailMarketing/sendTest/(:num)', 'EmailMarketing::sendTest/$1');
$routes->get('emailMarketing/sendNow/(:num)', 'EmailMarketing::sendNow/$1');
$routes->get('emailMarketing/schedule/(:num)', 'EmailMarketing::schedule/$1');
$routes->post('emailMarketing/schedule/(:num)', 'EmailMarketing::schedule/$1');
$routes->get('emailMarketing/cancel/(:num)', 'EmailMarketing::cancel/$1');
$routes->get('emailMarketing/delete/(:num)', 'EmailMarketing::delete/$1');
$routes->get('emailMarketing/processScheduled', 'EmailMarketing::processScheduled');
$routes->get('emailMarketing/templates', 'EmailMarketing::templates');
$routes->get('emailMarketing/getTemplate/(:any)', 'EmailMarketing::getTemplate/$1');
$routes->get('emailMarketing/trackOpen/(:num)/(:any)', 'EmailMarketing::trackOpen/$1/$2');
$routes->get('emailMarketing/trackClick/(:num)/(:any)/(:any)', 'EmailMarketing::trackClick/$1/$2/$3');

// Packages routes
$routes->get('packages', 'Packages::index');
$routes->get('packages/view/(:num)', 'Packages::view/$1');
$routes->get('packages/purchase/(:num)', 'Packages::purchase/$1');
$routes->get('packages/benefits', 'Packages::benefits');
$routes->get('packages/manage', 'Packages::manage');
$routes->get('packages/create', 'Packages::create');
$routes->post('packages/create', 'Packages::create');
$routes->get('packages/edit/(:num)', 'Packages::edit/$1');
$routes->post('packages/edit/(:num)', 'Packages::edit/$1');
$routes->get('packages/delete/(:num)', 'Packages::delete/$1');
$routes->get('packages/subscribers/(:num)', 'Packages::subscribers/$1');
$routes->get('packages/processExpiration', 'Packages::processExpiration');

// Levels routes
$routes->get('levels', 'Levels::index');
$routes->get('levels/points', 'Levels::points');
$routes->get('levels/achievements', 'Levels::achievements');
$routes->get('levels/dailyBonus', 'Levels::dailyBonus');
$routes->get('levels/manage', 'Levels::manage');
$routes->get('levels/create', 'Levels::create');
$routes->post('levels/create', 'Levels::create');
$routes->get('levels/edit/(:num)', 'Levels::edit/$1');
$routes->post('levels/edit/(:num)', 'Levels::edit/$1');
$routes->get('levels/delete/(:num)', 'Levels::delete/$1');
$routes->get('levels/manageAchievements', 'Levels::manageAchievements');
$routes->get('levels/createAchievement', 'Levels::createAchievement');
$routes->post('levels/createAchievement', 'Levels::createAchievement');
$routes->get('levels/editAchievement/(:num)', 'Levels::editAchievement/$1');
$routes->post('levels/editAchievement/(:num)', 'Levels::editAchievement/$1');
$routes->get('levels/deleteAchievement/(:num)', 'Levels::deleteAchievement/$1');
$routes->get('levels/awardPoints', 'Levels::awardPoints');
$routes->post('levels/awardPoints', 'Levels::awardPoints');
$routes->get('levels/resetAchievements/(:num)', 'Levels::resetAchievements/$1');

$routes->get('admin/(:segment)', 'ViewsController::admin/$1');
$routes->get('play/(:segment)', 'ViewsController::play/$1');

$routes->post('pusher/auth', 'PusherAuth::auth');

$routes->group('api/games', static function ($routes) {
    $routes->post('(:segment)/join', 'GamesNew::join/$1');
    $routes->post('(:segment)/draw', 'GamesNew::draw/$1');
    $routes->post('(:segment)/auto-draw', 'GamesNew::autoDraw/$1');
    $routes->post('(:segment)/claim-bingo', 'GamesNew::claimBingo/$1');
    $routes->post('(:segment)/reset', 'GamesNew::reset/$1');
    $routes->get('(:segment)/state', 'GamesNew::state/$1');
});