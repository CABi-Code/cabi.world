<?php

switch ($apiRoute) {
	case '/auth/login': include_once 'auth/login.php'; break;

	case '/auth/register': include_once 'auth/register.php'; break;

	case '/auth/refresh': include_once 'auth/refresh.php'; break;

	case '/user/update': include_once 'user/update.php'; break;

	case '/user/avatar': include_once 'user/avatar/avatar.php'; break;

	case '/user/avatar/delete': include_once 'user/avatar/delete.php'; break;

	case '/user/banner': include_once 'user/banner/banner.php'; break;

	case '/user/banner/delete': include_once 'user/banner/delete.php'; break;

	case '/feed': include_once 'feed/feed.php'; break;
	
	case '/modpack/apply': include_once 'modpack/apply.php'; break;

	case '/application/update': include_once 'application/update.php'; break;

	case '/application/delete': include_once 'application/delete.php'; break;

	case '/application/toggle-hidden': include_once 'application/toggle-hidden.php'; break;

	case '/notifications': include_once 'notifications/notifications.php'; break;

	case '/notifications/read': include_once 'notifications/read.php'; break;

	default:
		json(['error' => 'Not found'], 404);
}

?>