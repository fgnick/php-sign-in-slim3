Please execute mailer-for-os.php by system() in php file.
The better way is to work on the Linux OS.

E.g.
    system('nohup php '.$this->settings['os_script_url'].' '.$this->m_settings.' '.$out.' >> '.$this->settings['log_url'].' 2>&1 &');