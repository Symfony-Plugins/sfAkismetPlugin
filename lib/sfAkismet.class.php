<?php

class sfAkismet {

  private $browser;
  private $host;
  private $key;

  protected $api_version = '1.1';

  public function  __construct() {
    
    $this->host = sfConfig::get('app_akismet_host', 'rest.akismet.com');
    $this->key  = sfConfig::get('app_akismet_key', null);

    if (!$this->key) {
      throw new sfException('No Akismet key has been set');
    }

    $this->browser = new sfWebBrowser(array(
      'timeout'    => 5,
      'user_agent' => 'Symfony/'.sfConfig::get('sf_version').' | sfAkismet/1.0'
    ));

  }

  public function isValid() {

    $request = sfContext::getInstance()->getRequest();
    $params  = array(
      'blog' => 'http://'.$request->getHost(),
      'key'  => $this->key
    );

    try {
      if ($this->browser->post('http://'.$this->host.'/'.$this->api_version.'/verify-key', $params)->responseIsError()) {
        throw new sfException(sprintf('The given URL (%s) returns an error (%s: %s)', 'http://'.$this->host.'/'.$this->api_version.'/verify-key', $this->browser->getResponseCode(), $this->browser->getResponseMessage()));
      }
    }
    catch (Exception $e) {
      throw new sfException('Could not contact Akismet server '.$e);
    }

    return strtolower($this->browser->getResponseText()) == 'valid';
  }

  public function isSpam(array $params) {
    return strtolower($this->submit($params, 'comment-check')) == 'true';
  }

  public function setSpam(array $params) {
    $this->submit($params, 'submit-spam');
  }

  public function setHam(array $params) {
    $this->submit($params, 'submit-ham');
  }

  private function submit(array $params, $url) {

    $request = sfContext::getInstance()->getRequest();
    $params  = array_merge($_SERVER, array(
      'blog'       => 'http://'.$request->getHost(),
      'user_ip'    => $request->getRemoteAddress(),
      'user_agent' => $request->getHttpHeader('User-Agent')
    ), $params);

    try {
      if ($this->browser->post('http://'.$this->key.'.'.$this->host.'/'.$this->api_version.'/'.$url, $params)->responseIsError()) {
        throw new sfException(sprintf('The given URL (%s) returns an error (%s: %s)', 'http://'.$this->key.'.'.$this->host.'/'.$this->api_version.'/'.$url, $this->browser->getResponseCode(), $this->browser->getResponseMessage()));
      }
    }
    catch (Exception $e) {
      throw new sfException('Could not contact Akismet server '.$e);
    }

    return $this->browser->getResponseText();
  }

}