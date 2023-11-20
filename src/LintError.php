<?php

namespace CucumberLinter;

/**
 * @phpstan-type SerializedError array{message: string, file: string, line: ?int, filePath: ?string, tip: ?string}
 */
class LintError implements \JsonSerializable {

  public function __construct(
    private string $message,
    private string $file,
    private ?int $line = NULL,
    private ?string $filePath = NULL,
    private ?string $tip = NULL,
  ) {}

  public function getMessage() : string {
    return $this->message;
  }

  public function getFile() : string {
    return $this->file;
  }
  public function getFilePath() : string {
    if ($this->filePath === NULL) {
      return $this->file;
    }
    return $this->filePath;
  }

  public function getLine() : ?int {
    return $this->line;
  }

  public function getTip() : ?string {
    return $this->tip;
  }

  public function withoutTip() : self {
    if ($this->tip === null) {
      return $this;
    }
    return new self($this->message, $this->file, $this->line, $this->filePath, null);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return SerializedError
   */
  public function jsonSerialize() : array {
    return [
      "message" => $this->message,
      "file" => $this->file,
      "line" => $this->line,
      "filePath" => $this->filePath,
      "tip" => $this->tip,
    ];
  }

  /**
   * @phpstan-param SerializedError $json
   */
  public static function decode(array $json) : self {
    return new self($json['message'], $json['file'], $json['line'], $json['filePath'], $json['tip']);
  }
  /**
   * @phpstan-param SerializedError $properties
   */
  public static function __set_state(array $properties) : self {
    return new self($properties['message'], $properties['file'], $properties['line'], $properties['filePath'], $properties['tip']);
  }

}
