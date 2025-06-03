<?php

namespace Jap\Network;

class Snmp {

  // ***************************************************************************

  const SERVICE_PORT_SNMP = 161;
  const SERVICE_TEST_TIMEOUT = 1;

  protected $tag;
  protected $logger;

  function __construct($app) {
    $this->tag = 'SNMP';
    $this->logger = $app->logger;
    $this->logger->info($this->tag);
  }

  function __destruct() {
  }

  // ***************************************************************************

  function testSnmpService ($agent) {
    // error_log("testSnmpService ({$agent})");
    // SNMP: port 161 UDP
    // UDP ports cannot be tested this way
    // $resultTest = testNetworkService("udp://{$agent}", SERVICE_PORT_SNMP, SERVICE_TEST_TIMEOUT);
    $resultTest = $this->testNetworkService("{$agent}", self::SERVICE_PORT_SNMP, self::SERVICE_TEST_TIMEOUT);
    return $resultTest;
  }

  // http://stackoverflow.com/questions/1239068/ping-site-and-return-result-in-php
  function testNetworkService($host, $port, $timeout) {
    $tB = microtime(true);
    $fP = @fsockopen($host, $port, $errno, $errstr, $timeout);
    // $errno: 110 - Connection timed out (no device or firewall dropping packets)
    // $errno: 111 - Connection refused (closed port, device present)
    $result = ($errno == 0) || ($errno == 111);
    return $result;
  }

  // ***************************************************************************

  function snmpSession($snmpVersion, $agent, $params) {
    switch ($snmpVersion) {
      case '1':
        $tagVersion = \SNMP::VERSION_1;
        $community = $params['community'];
        break;

        case '2c':
        $tagVersion = \SNMP::VERSION_2C;
        $community = $params['community'];
        break;

      case '3':
        $tagVersion = \SNMP::VERSION_3;
        $community = $params['secName'];
        break;

      default:
    }

    $snmpSess = new \SNMP($tagVersion, $agent, $community);
    if ($snmpVersion == '3') {
      $secLevel = $params['secLevel'];
      $authProtocol = $params['authProtocol'];
      $authPassword = $params['authPassword'];
      $privProtocol = $params['privProtocol'];
      $privPassword = $params['privPassword'];
      $snmpSess->setSecurity($secLevel, $authProtocol, $authPassword, $privProtocol, $privPassword);
    }
    $snmpSess->valueretrieval = SNMP_VALUE_LIBRARY; // SNMP_VALUE_OBJECT;
    $snmpSess->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;
    // $snmpSess->oid_increasing_check = false;
    return $snmpSess;
  }

  // ***************************************************************************

  // 20220922: Add support for SNMP v3
  function snmpWalk($snmpVersion, $agent, $params, $oid) {

    $tag = "{$this->tag}->snmpWalk({$agent}, {$snmpVersion}, authParams, {$oid})";
    $this->logger->info($tag);

    $snmpSess = $this->snmpSession($snmpVersion, $agent, $params);
    
    $snmpRes = $snmpSess->walk($oid); // PHP Warning:  SNMP::walk(): Multi OID walks are not supported!
    $this->logger->info("walk()");
    if ($snmpRes === false) {
      $this->logger->error("SNMP Error #" . $snmpSess->getErrno());
      $this->logger->error($snmpSess->getError());
    }
    $snmpSess->close();
    $snmpRes = $this->unquote($snmpRes);
    return $snmpRes;
  }
  
  // ***************************************************************************

  function snmpGet($snmpVersion, $agent, $params, $oids) {

    $tag = "{$this->tag}->snmpGet({$agent}, {$snmpVersion}, <authParams>, <oids>)";
    $this->logger->info($tag);

    $snmpSess = $this->snmpSession($snmpVersion, $agent, $params);
    
    $snmpRes = $snmpSess->get($oids);
    $this->logger->info("get()");
    if ($snmpRes === false) {
      $this->logger->error("SNMP Error #" . $snmpSess->getErrno());
      $this->logger->error($snmpSess->getError());
    }
    $snmpSess->close();
    $snmpRes = $this->unquote($snmpRes);
    return $snmpRes;
  }

  // ***************************************************************************

  function unquote($snmpRes) {
    $tag = "{$this->tag}->unquote()";
    $this->logger->info($tag);

    foreach ($snmpRes as $key => $value) {
      // echo $value . "\n";

      // Step 1: Remove ["]...["]
      $pattern = '/STRING: "(.+)"/';
      $repl = 'STRING: ${1}';
      $snmpRes[$key] = preg_replace($pattern, $repl, $value);
      // echo $snmpRes[$key] . "\n";

      // Step 2: Remove [\"]...[\"]
      $pattern2 = '/STRING: \\\"(.+)\\\"/';
      $repl2 = 'STRING: ${1}';
      $snmpRes[$key] = preg_replace($pattern2, $repl2, $snmpRes[$key]);
      
      // $this->logger->info($key);
      // $this->logger->info($value);
    }
    return $snmpRes;
  }

  // ***************************************************************************

  // Algunos resultados de tipo string se entregan entrecomillados, lo cual da lugar a problemas en la conversión a json
  // Ya que hay que procesar el resultado, se separa en tipo y valor
  function sanitize($res) {
    // PCRE dotall modifier: https://stackoverflow.com/a/12111990/176974
    if (preg_match("/([\S]+): (.+)$/s", $res, $matches)) {
      // print_r($matches);
      $resArr = array(
        'type' => $matches[1],
        'value' => trim($matches[2], '"')
      );
    }
    else {
      $resArr = $res;
    }
    return $resArr;
  }

  // ***************************************************************************

}
