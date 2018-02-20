<?php
/*
 * Copyright (C) 2018 Moneybird
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Author: Robbin Voortman (robbin@moneybird.com)
 * Date: 20-02-2018
 */
final class DetektLinter extends ArcanistExternalLinter {
  private $jarPath = null;
  private $detektConfig = null;
  private $outputName = 'result';

  public function getInfoURI() {
    return 'https://github.com/moneybird/arc-detekt-linter';
  }

  public function getInfoDescription() {
    return 'Detekt linter for Kotlin';
  }

  public function getLinterName() {
    return 'detekt';
  }

  public function getLinterConfigurationName() {
    return 'detekt';
  }

  public function getDefaultBinary() {
    return 'java';
  }

  public function getInstallInstructions() {
    return 'You need to have a detekt Jar. See https://github.com/arturbosch/detekt';
  }

  public function shouldExpectCommandErrors() {
    return true;
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'jar' => array(
        'type' => 'optional string',
        'help' => pht(
          'Specify a string identifying the Detekt JAR file.'),
      ),
      'detektConfig' => array(
        'type' => 'optional string',
        'help' => pht(
          'Specify a string identifying the Detekt.yml file'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  /**
   * Set the configuration values.
   */
  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'jar':
        foreach ((array)$value as $path) {
          if ($this->pathExist($path)) {
            $this->jarPath = $path;
            return;
          } else {
            throw new ArcanistUsageException(
              pht('The detekt JAR could not be found. Please check the path in your config.')
            );
          }
        }
     case 'detektConfig':
      foreach ((array)$value as $path) {
        if ($this->pathExist($path)) {
            $this->detektConfig = $path;
            return;
          } else {
            throw new ArcanistUsageException(
              pht('The detekt.yml could not be found. Please check the path in your config.')
            );
          }
        }
    }

    return parent::setLinterConfigurationValue($key, $value);
  }

  protected function getMandatoryFlags() {
    if ($this->jarPath === null) {
      throw new ArcanistUsageException(
        pht('Detekt JAR path is not yet configured. Please specify in .arclint the jar path.'));
    }

    return array(
      '-jar',
      $this->jarPath,
      '--output',
      dirname(__FILE__).'/' ,
      '--output-name',
      $this->outputName,
      '-c',
      $this->detektConfig ?: '.',
      '--input',
    );
  }

  protected function getDefaultFlags() {
    return array();
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $file = dirname(__FILE__).'/'.$this->outputName.'.xml';
    if (!file_exists($file)) { return []; }

    $xml_string = file_get_contents($file);
    $xml = simplexml_load_string($xml_string)->{'file'};

    $violations = $xml->{'error'};
    if ($violations === null) { return []; }

    $messages = [];

    foreach ($violations as $error) {
      $violation          = $this->parseViolation($error);
      $violation['path']  = $path;
      $messages[]         = ArcanistLintMessage::newFromDictionary($violation);
    }

    $this->cleanGeneratedFiles();

    return $messages;
  }

  private function parseViolation(SimpleXMLElement $xml) {
      return array(
          'code'        => $this->getLinterName(),
          'name'        => (string)str_replace('detekt.', '', $xml['source']),
          'line'        => (int)$xml['line'],
          'char'        => (int)$xml['column'],
          'severity'    => $this->getArcanistSeverity((string)$xml['severity']),
          'description' => (string)$xml['message'],
      );
  }

  /**
   * Clean up the generated files from Detekt
   */
  private function cleanGeneratedFiles() {
    $generated_extensions = ['html', 'txt', 'xml'];
    foreach ($generated_extensions as $extension) {
      $file_path = dirname(__FILE__).'/'.$this->outputName.'.'.$extension;
      if (file_exists($file_path)) {
        unlink($file_path);
      }
    }
  }

  /**
   * Check if a path exists, here or one level higher.
   */
  private function pathExist($path) {
    $working_copy = $this->getEngine()->getWorkingCopy();
    $root = $working_copy->getProjectRoot();

    if (Filesystem::pathExists($path)) {
      return true;
    }

    $path = Filesystem::resolvePath($path, $root);
    if (Filesystem::pathExists($path)) {
      return true;
    }
    return false;
  }

  /**
   * Match the severity string to an ArcanistLintSeverity
   */
  private function getArcanistSeverity($severity_name) {
      $map = array(
          'error' => ArcanistLintSeverity::SEVERITY_ERROR,
          'warning' => ArcanistLintSeverity::SEVERITY_WARNING,
          'info' => ArcanistLintSeverity::SEVERITY_ADVICE,
      );
      foreach ($map as $name => $severity) {
          if ($severity_name == $name) {
              return $severity;
          }
      }
      return ArcanistLintSeverity::SEVERITY_WARNING;
  }
}
