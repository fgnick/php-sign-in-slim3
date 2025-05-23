<?php
namespace Gn\Interfaces;
/**
 * All http response default message string are collected in this interface for implementing
 * 
 * @author Nick Feng
 * @since 2019-08-30
 */
interface MailerInterface {
    const MAIL_TITLE_INVITATION    = 'Invitation Email';
    const MAIL_TITLE_PW_CHANGED    = 'Your Password Changed';
    const MAIL_TITLE_PW_RESET      = 'Your Password Reset';
    const MAIL_TITLE_ACCESS_OTP    = 'Access OTP';
    
    const MAIL_HTMLBODY_INVITATION = 'invitee-confirm.phtml';
    const MAIL_HTMLBODY_PW_CHANGED = 'account-pw-changed.phtml';
    const MAIL_HTMLBODY_PW_RESET   = 'account-pw-reset.phtml';
    const MAIL_HTMLBODY_ACCESS_OTP = 'access-otp.phtml';
}