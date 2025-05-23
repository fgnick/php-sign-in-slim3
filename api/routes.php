<?php
/**
 * Application route of Slim 3
 * 
 * @author Nick Feng
 * @since 1.0
 */
$app->group('/v1', function () use ($app) {
    // ---------------- system member management (system framework) ---------------- [START]
    $app->group('/account', function () {
        $this->get('/profile',    Gn\MemberCtrl::class.':get_AccountProfile');
        $this->post('/profile',   Gn\MemberCtrl::class.':post_EditAccountProfile');
        $this->post('/pw/change', Gn\MemberCtrl::class.':post_ChangePassword');
    });
    
    $app->group('/role', function () {
        $this->get('/list',        Gn\MemberCtrl::class . ':post_RoleList');
        $this->post('/add',        Gn\MemberCtrl::class . ':post_CreateRole');
        $this->delete('',          Gn\MemberCtrl::class . ':del_RemoveRole');
        $this->get('/permission',  Gn\MemberCtrl::class . ':get_RolePermission');
        $this->post('/permission', Gn\MemberCtrl::class . ':post_RolePermission');
    });
    
    $app->group('/member', function () {
        $this->post('/invite',    Gn\MemberCtrl::class . ':post_InviteMember'); // like account register.
        $this->post('/re-invite', Gn\MemberCtrl::class . ':post_ReinviteMember'); // like account register.
        $this->get('/list',       Gn\MemberCtrl::class . ':get_GetMemberList');
        $this->get('/view/{id:[0-9]+}', Gn\MemberCtrl::class . ':get_GetMemberData');
        $this->post('/edit',      Gn\MemberCtrl::class . ':post_EditMember');
    });
    
    // for website asset that is protected by token. if you want to use a un-token protected asset, please use the API in /app/routes.php.
    $app->group('/asset', function () {
        $this->get('/phone/zone-code.json',      Gn\AssetCtrl::class . ':GET_PhoneZoneCode');
        $this->get('/address/country-code.json', Gn\AssetCtrl::class . ':GET_AddressCountryCode');
        $this->get('/address',                   Gn\AssetCtrl::class . ':GET_AddressCountryCode');
    });

    
    // --------------------------------- Customized API for yourself ------------------------------------------
   
    // .......... 
    
    // for system controlling
    $app->group('/system', function () use ( $app ) {
        // log review
        $app->get('/log', Gn\LogCtrl::class . ':GET_PrintSysTemLog'); // ?s=[timestamp]&c=[channel number]&l=[level number]
    });
});