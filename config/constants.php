<?php

/**
 * 系统常量配置
 */

/* ****** Table Names ****** */


/* ****** Rsa Keys ****** */

define('RSA_PR_KEY', DIR_APP . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'rsa_private_key.pem');
define('RSA_PB_KEY', DIR_APP . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'rsa_public_key.pem');
define('RSA_PB_KEY_BASE64', DIR_APP . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'rsa_public_key_base64.pem');

/* ****** Server Err Keys ****** */

define('ERR_SERVER', -1);
define('ERR_LOGIN', -403);
define('SERVER_ERR', 'server_err');
define('SERVER_ERR_DB', 'server_err_db');
define('SERVER_ERR_CONFIG', 'server_err_config');

/* ****** Resource Access Urls ****** */


/* common value */
define('MAX_INT32', 2147483647);
define('MAX_UINT32', 4294967295);
define('DAY_SECONDS', 86400);
