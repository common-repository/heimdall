<?php

namespace Heimdall;

defined('HEIMDALL_VER') || die;

class Cryptor
{

    private $cipher_algo;
    private $hash_algo;
    private $iv_num_bytes;
    private $format;

    const FORMAT_RAW     = 0;
    const FORMAT_B64     = 1;
    const FORMAT_HEX     = 2;

    function __construct($cipher_algo = 'aes-256-ctr', $hash_algo = 'sha256', $fmt = Cryptor::FORMAT_B64)
    {

        $this->cipher_algo     = $cipher_algo;
        $this->hash_algo     = $hash_algo;
        $this->format         = $fmt;

        if (!in_array($cipher_algo, openssl_get_cipher_methods(true))) {
            throw new \Exception("Cryptor:: - unknown cipher algo {$cipher_algo}");
        }

        if (!in_array($hash_algo, openssl_get_md_methods(true))) {
            throw new \Exception("Cryptor:: - unknown hash algo {$hash_algo}");
        }

        $this->iv_num_bytes = openssl_cipher_iv_length($cipher_algo);
    }


    function encrypt_string($in, $key, $fmt = null)
    {
        if ($fmt === null) {
            $fmt = $this->format;
        }

        // Build an initialisation vector
        $iv = openssl_random_pseudo_bytes($this->iv_num_bytes, $isStrongCrypto);
        if (!$isStrongCrypto) {
            throw new \Exception("Cryptor::encryptString() - Not a strong key");
        }

        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);

        // and encrypt
        $opts         = OPENSSL_RAW_DATA;
        $encrypted     = openssl_encrypt($in, $this->cipher_algo, $keyhash, $opts, $iv);

        if ($encrypted === false) {
            throw new \Exception('Cryptor::encryptString() - Encryption failed: ' . openssl_error_string());
        }

        // The result comprises the IV and encrypted data
        $res = $iv . $encrypted;

        // and format the result if required.
        if ($fmt == Cryptor::FORMAT_B64) {
            $res = base64_encode($res);
        } else if ($fmt == Cryptor::FORMAT_HEX) {
            $unp     = unpack('H*', $res);
            $res     = $unp[1];
        }

        return $res;
    }


    public function decrypt_string($in, $key, $fmt = null)
    {
        if ($fmt === null) {
            $fmt = $this->format;
        }

        $raw = $in;

        // Restore the encrypted data if encoded
        if ($fmt == Cryptor::FORMAT_B64) {
            $raw = base64_decode($in);
        } else if ($fmt == Cryptor::FORMAT_HEX) {
            $raw = pack('H*', $in);
        }

        // and do an integrity check on the size.
        if (strlen($raw) < $this->iv_num_bytes) {
            throw new \Exception('Cryptor::decryptString() - ' .
                'data length ' . strlen($raw) . " is less than iv length {$this->iv_num_bytes}");
        }

        // Extract the initialisation vector and encrypted data
        $iv     = substr($raw, 0, $this->iv_num_bytes);
        $raw     = substr($raw, $this->iv_num_bytes);

        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);

        // and decrypt.
        $opts     = OPENSSL_RAW_DATA;
        $res     = openssl_decrypt($raw, $this->cipher_algo, $keyhash, $opts, $iv);

        if ($res === false) {
            throw new \Exception('Cryptor::decryptString - decryption failed: ' . openssl_error_string());
        }

        return $res;
    }


    static function encrypt($in, $key, $fmt = null)
    {
        $c = new Cryptor();
        return $c->encrypt_string($in, $key, $fmt);
    }


    static function decrypt($in, $key, $fmt = null)
    {
        $c = new Cryptor();
        return $c->decrypt_string($in, $key, $fmt);
    }

}
