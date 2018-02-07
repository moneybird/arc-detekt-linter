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
 * Date: 07-02-2018
 */
final class DetektLinter extends ArcanistExternalLinter {

  private $jarPath = null;
  private $detektConfig = null;

  public function getInfoURI() {
    return 'https://github.com/arturbosch/detekt';
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

  protected function getMandatoryFlags() {
    if ($this->jarPath === null) {
      throw new ArcanistUsageException(
        pht('Detekt JAR path is not yet configured. Please specify in .arclint the jar path.'));
    }

    return array(
      '-jar',
      $this->jarPath,
      '-c',
      $this->detektConfig ?: '.',
      '--input',
    );
  }

  protected function getDefaultFlags() {
    return array();
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {

    $messages = array();

    $output = trim($stdout);
    if (strlen($output) === 0) {
      return $messages;
    }

    $lines = explode(PHP_EOL, $output);

    foreach ($lines as $line) {

      $matches = array();
      if (preg_match('/^\s*(?P<message>[aA-zZ]+?)\s-(.+\s-)*?\s\[.+?\]\sat\s(.*?):(?P<line>\d*?):\d*?$/m', $line, $matches)) {
        $lint_message = id(new ArcanistLintMessage())
           ->setPath($path)
           ->setCode($this->getLinterName())
           ->setName(trim($matches['message']))
           ->setLine(trim($matches['line']))
           ->setSeverity(ArcanistLintSeverity::SEVERITY_WARNING);

        $messages[] = $lint_message;
      }
    }
    return $messages;
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
}
