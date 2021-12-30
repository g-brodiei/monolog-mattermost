<?php

namespace Drupal\monolog_mattermost\Logger\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;
use Monolog\Utils;

/**
 * Mattermost formatter for monolog.
 */
class MattermostFormatter extends NormalizerFormatter implements FormatterInterface {

  /**
   * Convert level to Colors.
   *
   * @var array
   */
  private static $levels = [
    Logger::DEBUG => ' :white_check_mark: ',
    Logger::INFO => ' :white_check_mark: ',
    Logger::NOTICE => ' :cry: ',
    Logger::WARNING => ' :warning: ',
    Logger::ERROR => ' :exclamation: ',
    Logger::CRITICAL => ' :exclamation: ',
    Logger::ALERT => ' :ambulance: ',
    Logger::EMERGENCY => ' :ambulance: ',
  ];

  /**
   * {@inheritDoc}.
   */
  public function format(array $record) {
    $record = parent::format($record);
    $string = $this->setTitle($record);
    $string .= $this->setAttachmentText($record);
    $message = [
      'text' => $string,
    ];
    return Utils::jsonEncode($message);
  }

  /**
   * Generate message title
   *
   * @param array $record
   *
   * @return string
   */
  private function setTitle(array $record) {
    $title = [
      'title' => "#### " . $record['channel'] . "." . $record['level_name'] . self::$levels[$record['level']],
      'seperater' => '---',
      'space' => '',
    ];
    return implode(PHP_EOL, $title);
  }

  /**
     * Generate Attachment text
     *
     * @param array $record
     *
     * @return string
     */
    private function setAttachmentText(array $record)
    {
        $message = strlen($record['message']) > 3500 ? substr($record['message'], 0, 3500) : $record['message'];
        $content = [$message, ''];
        if (isset($record['datetime'])) {
            $message .= $record['datetime'];
        }

        if (!empty($record['context'])) {
            $content[] = $this->addMarkdownTable('context', $record['context']);
        }

        if (!empty($record['extra'])) {
            $content[] = $this->addMarkdownTable('extra', $record['extra']);
        }

        return implode(PHP_EOL, $content);
    }

    /**
     * Generate a Markdown table from an array
     *
     * @param string $table_name
     * @param array  $data
     *
     * @return string
     */
    private function addMarkdownTable($table_name, array $data)
    {
        $content = [
            "#### $table_name",
            '',
            '| Name | Value |',
            '|:---------|:---------|',
        ];

        foreach ($data as $name => $value) {
            $content[] = "| $name | ".$this->formatValue($value).' |';
        }

        $content[] = '';

        return implode(PHP_EOL, $content);
    }

    /**
     * Format value to be included in markdown table cell.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function formatValue($value)
    {
        if (is_array($value)) {
            return sprintf('```%s```', $this->stringify($value));
        }

        if (null === $value) {
            return '`null`';
        }

        if (is_bool($value)) {
            return $value ? '`true`' : '`false`';
        }

        return (string) $value;
    }

    /**
     * Stringifies an array of key/value pairs to be used in attachment fields
     *
     * @param array $fields
     *
     * @return string
     */
    public function stringify($fields)
    {
        $normalized = $this->formatter->format($fields);
        $prettyPrintFlag = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 128;

        $hasSecondDimension = count(array_filter($normalized, 'is_array'));
        $hasNonNumericKeys = !count(array_filter(array_keys($normalized), 'is_numeric'));

        return $hasSecondDimension || $hasNonNumericKeys ? json_encode($normalized, $prettyPrintFlag) : json_encode($normalized);
    }

}
