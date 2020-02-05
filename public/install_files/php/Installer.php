<?php

use App\User;
use Common\Settings\DotEnvEditor;

class Installer
{
    /**
     * @var string Application base path.
     */
    protected $baseDirectory;

    /**
     * Constructor/Router
     */
    public function __construct()
    {
        $this->baseDirectory = PATH_INSTALL;
        $this->logFile = PATH_INSTALL . '/public/install_files/install.log';
        $this->logPost();

        if (!is_null($handler = $this->post('handler'))) {
            if (!strlen($handler)) exit;

            try {
                if (!preg_match('/^on[A-Z]{1}[\w+]*$/', $handler)) throw new Exception(sprintf('Invalid handler: %s', $this->e($handler)));

                if (method_exists($this, $handler) && ($result = $this->$handler()) !== null) {
                    $this->log('Execute handler (%s): %s', $handler, print_r($result, true));
                    header('Content-Type: application/json');
                    die(json_encode($result));
                }
            } catch (Exception $ex) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                $this->log('Handler error (%s): %s', $handler, $ex->getMessage());
                $this->log(['Trace log:', '%s'], $ex->getTraceAsString());
                die($ex->getMessage());
            }

            exit;
        }
    }

    protected function onCheckRequirements()
    {
        $this->log('Check requirements: start');

        $result = [
            'PHP Version' => ['result' => version_compare(PHP_VERSION, MINIMUM_VERSION, '>'), 'errorMessage' => 'You need at least ' . MINIMUM_VERSION . ' PHP Version to install.'],
            'PROC_OPEN' => ['result' => function_exists('proc_open'), 'errorMessage' => 'PHP proc_open function needs to be enabled.'],
            'PDO' => ['result' => defined('PDO::ATTR_DRIVER_NAME'), 'errorMessage' => 'PHP PDO extension is required.',],
            'JSON' => ['result' => extension_loaded('json'), 'errorMessage' => 'PHP JSON extension is required.',],
            'Mbstring' => ['result' => extension_loaded('mbstring'), 'errorMessage' => 'PHP mbstring extension is required.',],
            'Fileinfo' => ['result' => extension_loaded('fileinfo'), 'errorMessage' => 'PHP fileinfo extension is required.'],
            'OpenSSL' => ['result' => extension_loaded('openssl'), 'errorMessage' => 'PHP openssl extension is required.'],
            'GD' => ['result' => extension_loaded('gd'), 'errorMessage' => 'PHP openssl extension is required.'],
            'Curl' => ['result' => extension_loaded('curl'), 'errorMessage' => 'PHP curl functionality needs to be enabled.'],
            'Zip' => ['result' => class_exists('ZipArchive'), 'errorMessage' => 'PHP ZipArchive extension needs to be installed.'],
        ];

        $allPass = array_filter($result, function($item) {
            return !$item['result'];
        });

        $this->log('Check requirements: end', ($allPass ? '+OK' : '=FAIL'));

        return $result;
    }

    protected function onCheckFileSystem()
    {
        $this->log('Check filesystem: start');

        $directories = [
            '',
            'storage',
            'storage/app',
            'storage/logs',
            'storage/framework',
            'public/storage',
            'resources/views/emails/custom',
        ];

        $message = 'Make this directory writable by giving it 777 permissions via file manager.';

        $results = [];

        foreach ($directories as $directory) {
            $path = rtrim("{$this->baseDirectory}/$directory", '/');
            $writable = is_writable($path);
            $results[] = ['path' => $path, 'result' => $writable, 'errorMessage' => $writable ? '' : $message];
        }

        $files = [
            '.env.example',
            '.htaccess',
            'public/.htaccess',
        ];

        foreach ($files as $file) {
            $filePath = "{$this->baseDirectory}/$file";
            $writable = is_writable($filePath);
            $content = $writable ? trim(file_get_contents($filePath)) : '';

            $results[] = [
                'path' => $filePath,
                'result' => $writable && strlen($content),
                'errorMessage' => "Make sure <strong>$file</strong> file has been uploaded properly to your server and is writable."
            ];
        }

        $allPass = array_filter($results, function($item) {
            return !$item['result'];
        });

        $this->log('Check filesystem: end', $results, ($allPass ? '+OK' : '=FAIL'));

        return $results;
    }

    protected function onValidateAndInsertDatabaseCredentials()
    {
        if (!strlen($this->post('db_host'))) throw new InstallerException('Please specify a database host.', 'db_host');

        if (!strlen($this->post('db_database'))) throw new InstallerException('Please specify the database name.', 'db_database');

        $config = ['db_host' => null, 'db_database' => null, 'db_port' => null, 'db_username' => null, 'db_password' => null, 'db_prefix' => null];
        array_walk($config, function (&$value, $key) {
            $value = $value ?: $this->post($key);
        });

        $dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_database'];
        if ($config['port']) $dsn .= ";port=" . $config['port'];

        try {
            $db = new PDO($dsn, $config['db_username'], $config['db_password'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (PDOException $ex) {
            throw new Exception('Connection failed: ' . $ex->getMessage());
        }

        /*
         * Check the database is empty
         */
        $fetch = $db->query('show tables', PDO::FETCH_NUM);

        $tables = 0;
        while ($result = $fetch->fetch()) $tables++;

        if ($tables > 0) {
            throw new Exception(sprintf('Database "%s" is not empty. Please empty the database or specify another database.', $this->e($config['db_database'])));
        }

        $this->insertDBCredentials($config);
    }

    protected function onValidateAdminAccount()
    {
        if (!strlen($this->post('email'))) throw new InstallerException('Please specify administrator email address', 'email');

        if (!filter_var($this->post('email'), FILTER_VALIDATE_EMAIL)) throw new InstallerException('Please specify valid email address', 'email');

        if (!strlen($this->post('password'))) throw new InstallerException('Please specify password', 'password');

        if (strlen($this->post('password')) < 4) throw new InstallerException('Please specify password length more than 4 characters', 'password');

        if (strlen($this->post('password')) > 255) throw new InstallerException('Please specify password length less than 64 characters', 'password');

        if (!strlen($this->post('password_confirmation'))) throw new InstallerException('Please confirm chosen password', 'password_confirmation');

        if (strcmp($this->post('password'), $this->post('password_confirmation'))) throw new InstallerException('Specified password does not match the confirmed password', 'password');
    }

    protected function onInstallApplication()
    {
        $this->bootFramework();

        //fix "index is too long" issue on MariaDB and older mysql versions
        Schema::defaultStringLength(191);

        Artisan::call('key:generate', ['--force' => true]);
        Artisan::call('migrate', ['--force' => true]);

        $this->createAdminAccount();

        Artisan::call('db:seed', ['--force' => true]);
        Artisan::call('common:seed');

        $this->putAppInProductionEnv();

        Cache::flush();

        try {
            $this->deleteInstallationFiles();
        } catch (Exception $e) {
            //
        }
    }

    public function createAdminAccount()
    {
        $email = $this->post('email');
        $user = app(User::class)->firstOrNew(['email' => $email]);
        $user->username = $this->post('username');
        $user->email = $email;
        $user->password = Hash::make($this->post('password'));
        $user->permissions = ['admin' => 1, 'superAdmin' => 1];
        $user->save();

        Auth::login($user);
    }


    /**
     * Insert user supplied db credentials into .env file.
     *
     * @param array $credentials
     * @return void
     */
    protected function insertDBCredentials($credentials)
    {
        $this->bootFramework();

        $envFile = $this->baseDirectory . '/.env';
        $envExampleFile = $this->baseDirectory . '/.env.example';
        $envExists = file_exists($envFile);

        $writer = new DotEnvEditor($envExists ? '.env' : '.env.example');
        $writer->write($credentials);

        if ( ! $envExists) {
            // rename .env.example to .env
            rename($envExampleFile, $envFile);
        }
    }

    private function putAppInProductionEnv()
    {
        $writer = app(DotEnvEditor::class);
        $writer->write([
            'app_url' => $this->getBaseUrl(),
            'app_env' => 'production',
            'app_debug' => false,
            'installed' => true,
        ]);
    }

    private function deleteInstallationFiles()
    {
        $dir = $this->baseDirectory . '/public/install_files';

        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        @rmdir($dir);
    }

    public function cleanLog()
    {
        file_put_contents($this->logFile, '"========================== INSTALLATION LOG ========================"');
    }

    public function logPost()
    {
        if (!isset($_POST) || !count($_POST)) return;
        $postData = $_POST;

        if (array_key_exists('disableLog', $postData)) $postData = array('disableLog' => true);

        /*
         * Sensitive data fields
         */
        if (isset($postData['admin_email'])) $postData['admin_email'] = '*******@*****.com';
        $fieldsToErase = array('encryption_code', 'admin_password', 'admin_confirm_password', 'db_pass', 'project_id',);
        foreach ($fieldsToErase as $field) {
            if (isset($postData[$field])) $postData[$field] = '*******';
        }

        file_put_contents($this->logFile, '.============================ POST REQUEST ==========================.' . PHP_EOL, FILE_APPEND);
        $this->log('Postback payload: %s', print_r($postData, true));
    }

    public function log()
    {
        $args = func_get_args();
        $message = array_shift($args);

        if (is_array($message)) $message = implode(PHP_EOL, $message);

        $message = "[" . date("Y/m/d h:i:s", time()) . "] " . vsprintf($message, $args) . PHP_EOL;
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }

    protected function bootFramework()
    {
        $autoloadFile = $this->baseDirectory . '/bootstrap/autoload.php';
        if (!file_exists($autoloadFile)) {
            throw new Exception('Unable to find autoloader: ~/bootstrap/autoload.php');
        }
        require $autoloadFile;

        $appFile = $this->baseDirectory . '/bootstrap/app.php';
        if (!file_exists($appFile)) {
            throw new Exception('Unable to find app loader: ~/bootstrap/app.php');
        }
        $app = require_once $appFile;
        $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
        $kernel->bootstrap();
    }

    protected function post($var, $default = null)
    {
        if (array_key_exists($var, $_REQUEST)) {
            $result = $_REQUEST[$var];
            if (is_string($result)) $result = trim($result);
            return $result;
        }

        return $default;
    }

    public function getBaseUrl()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $baseUrl = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $baseUrl .= '://' . $_SERVER['HTTP_HOST'];
            $baseUrl .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        } else {
            $baseUrl = 'http://localhost/';
        }

        $baseUrl = rtrim($baseUrl, '/');
        $baseUrl = preg_replace('/\/public$/', '', $baseUrl);
        $baseUrl = str_replace('install_files', '', $baseUrl);

        return trim($baseUrl);
    }

    public function e($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
}
