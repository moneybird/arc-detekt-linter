<?php

// source: https://secure.phabricator.com/D10405
// author: josh-newman
final class DetektLinter extends ArcanistExternalLinter {

  private $jarPath = null;
  private $configPath = null;

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
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'jar':
        $working_copy = $this->getEngine()->getWorkingCopy();
        $root = $working_copy->getProjectRoot();

        foreach ((array)$value as $path) {
          if (Filesystem::pathExists($path)) {
            $this->jarPath = $path;
            return;
          }

          $path = Filesystem::resolvePath($path, $root);
          if (Filesystem::pathExists($path)) {
            $this->jarPath = $path;
            return;
          }
        }

        throw new ArcanistUsageException(
          pht('The detekt JAR could not be found. Please check the path in your config.'));
    }

    return parent::setLinterConfigurationValue($key, $value);
  }

}
