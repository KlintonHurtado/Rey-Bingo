<?php

declare(strict_types=1);

namespace Config;

use App\Libraries\ResendEmail;
use CodeIgniter\Config\BaseService;
use Config\Email as EmailConfig;

/**
 * Services Configuration file.
 */
class Services extends BaseService
{
    /**
     * Retorna el servicio de Email utilizando App\Libraries\ResendEmail,
     * permitiendo soporte transparente de Resend (REST API / HTTPS) y SMTP.
     *
     * @param array|EmailConfig|null $config
     * @param bool                   $getShared
     *
     * @return ResendEmail
     */
    public static function email($config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('email', $config);
        }

        if (empty($config) || (! is_array($config) && ! $config instanceof EmailConfig)) {
            $config = config(EmailConfig::class);
        }

        return new ResendEmail($config);
    }
}
