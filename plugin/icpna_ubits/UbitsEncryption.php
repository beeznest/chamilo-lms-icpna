<?php
/* For licensing terms, see /license.txt */

/**
 * Class UbitsEncryption.
 *
 * Manage the encryption process for UBITS.
 */
class UbitsEncryption
{
    /**
     * @var string Private key.
     */
    private $privateKey;
    /**
     * @var string Public key.
     */
    private $publicKey;
    /**
     * @var string Message nonce.
     */
    private $messageNonce;

    /**
     * UbitsEncryption constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $privateKey = $this->getFileContent(__DIR__.'/keys/private_key.key');
        $publicKey = $this->getFileContent(__DIR__.'/keys/u_public_key.key');
        $messageNonce = $this->getFileContent(__DIR__.'/keys/message_nonce.key');

        if (!$publicKey) {
            throw new Exception("The public key is empty.");
        }

        if (!$privateKey) {
            throw new Exception("The private key is empty.");
        }

        if (!$messageNonce) {
            throw new Exception("The message nonce is empty.");
        }

        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->messageNonce = $messageNonce;
    }

    /**
     * @param string $timeserver
     * @param int    $socket
     *
     * @return array
     */
    private function queryTimeServer($timeserver, $socket) {
        /* Query a time server
          (C) 1999-09-29, Ralf D. Kloth (QRQ.software) <ralf at qrq.de> */

        $fp = fsockopen($timeserver, $socket, $err, $errstr, 5);
        // parameters: server, socket, error code, error text, timeout
        if ($fp) {
            fputs($fp, "\n");
            $timevalue = fread($fp, 49);
            fclose($fp); # close the connection
        } else {
            $timevalue = " ";
        }

        $ret = array();
        $ret[] = $timevalue;
        $ret[] = $err;     // error code
        $ret[] = $errstr;  // error text

        return($ret);
    }

    /**
     * @return false|string
     */
    private function getCurrentTimeFromServer() {
        $timeserver = "time-C.timefreq.bldrdoc.gov";
        $timercvd = $this->queryTimeServer($timeserver, 13);

        if (empty($timercvd[1])) {
            $strTime = explode(" ", $timercvd[0]);
            $date = $strTime[1];
            $time = $strTime[2];

            $currentTime = "$date $time";
        } else {
            $currentTime = date_format(date(), 'Y-m-d H:i:s');
        }

        return $currentTime;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getFileContent($path) {
        $myFile = $path;
        $myFileLink = fopen($myFile, 'r');
        $myFileContents = fread($myFileLink, filesize($myFile));
        fclose($myFileLink);

        return $myFileContents;
    }

    /**
     * @param string $uuid
     * @param string $username
     *
     * @throws SodiumException
     *
     * @return string
     */
    public function encrypt($uuid, $username) {
        $text = "$uuid|$username|".$this->getCurrentTimeFromServer();

        $kp = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $this->privateKey,
            $this->publicKey
        );

        $ciphertext = sodium_crypto_box(
            $text,
            $this->messageNonce,
            $kp
        );

        $result = rawurlencode(base64_encode($ciphertext));

        return $result;
    }
}
