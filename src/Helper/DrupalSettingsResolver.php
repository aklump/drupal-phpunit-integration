<?php

namespace AKlump\Drupal\PHPUnit\Integration\Helper;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\ParserFactory;

class DrupalSettingsResolver {

  private $parser;

  private $visitedFiles = [];

  private $databases = [];

  public function __construct() {
    $factory = new ParserFactory();
    $this->parser = $factory->createForHostVersion();
  }

  public function resolve(string $filePath): array {
    $this->visitedFiles = [];
    $this->databases = [];
    $this->processFile($filePath);

    return $this->databases;
  }

  private function processFile(string $filePath) {
    $realPath = realpath($filePath);
    if (!$realPath || isset($this->visitedFiles[$realPath])) {
      return;
    }
    $this->visitedFiles[$realPath] = TRUE;

    if (!is_file($realPath)) {
      return;
    }

    $code = file_get_contents($realPath);
    try {
      $stmts = $this->parser->parse($code);
      if ($stmts) {
        $this->processStatements($stmts, dirname($realPath));
      }
    } catch (Error $e) {
      // Skip files with syntax errors.
    }
  }

  private function processStatements(array $stmts, string $currentDir) {
    foreach ($stmts as $stmt) {
      if ($stmt instanceof Node\Stmt\Expression) {
        $stmt = $stmt->expr;
      }
      if ($stmt instanceof Assign) {
        if ($stmt->var instanceof Node\Expr\ArrayDimFetch) {
          $this->evaluateDatabaseDimFetch($stmt->var, $stmt->expr);
        } elseif ($this->isDatabasesVar($stmt->var)) {
          $this->evaluateDatabases($stmt->expr);
        }
      } elseif ($stmt instanceof Include_) {
        $includedPath = $this->resolvePath($stmt->expr, $currentDir);
        if ($includedPath) {
          $this->processFile($includedPath);
        }
      }
      // Recursively check statements (e.g., inside if blocks)
      if (isset($stmt->stmts) && is_array($stmt->stmts)) {
        $this->processStatements($stmt->stmts, $currentDir);
      }
      if (isset($stmt->else) && $stmt->else instanceof Node\Stmt\Else_) {
        $this->processStatements($stmt->else->stmts, $currentDir);
      }
      if (isset($stmt->elseifs) && is_array($stmt->elseifs)) {
        foreach ($stmt->elseifs as $elseif) {
          $this->processStatements($elseif->stmts, $currentDir);
        }
      }
    }
  }

  private function isDatabasesVar(Node $node): bool {
    return $node instanceof Variable && $node->name === 'databases';
  }

  private function evaluateDatabaseDimFetch(Node\Expr\ArrayDimFetch $dimFetch, Node\Expr $expr) {
    $keys = [];
    $current = $dimFetch;
    while ($current instanceof Node\Expr\ArrayDimFetch) {
      if ($current->dim instanceof String_) {
        array_unshift($keys, $current->dim->value);
      } else {
        // Dynamic key, we skip this for now.
        return;
      }
      $current = $current->var;
    }

    if (!$this->isDatabasesVar($current)) {
      return;
    }

    $value = $this->evaluateExpr($expr);
    if ($value === null && !($expr instanceof Node\Scalar\String_ || $expr instanceof Array_)) {
      return;
    }

    $ref = &$this->databases;
    foreach ($keys as $key) {
      if (!isset($ref[$key]) || !is_array($ref[$key])) {
        $ref[$key] = [];
      }
      $ref = &$ref[$key];
    }
    $ref = $value;
  }

  private function evaluateExpr(Node\Expr $expr) {
    if ($expr instanceof String_) {
      return $expr->value;
    }
    if ($expr instanceof Array_) {
      return $this->nodeToArray($expr);
    }

    return null;
  }

  private function resolvePath(Node\Expr $expr, string $currentDir): ?string {
    if ($expr instanceof String_) {
      $path = $expr->value;
    } elseif ($expr instanceof Node\Scalar\MagicConst\Dir) {
        return $currentDir;
    } elseif ($expr instanceof Node\Expr\BinaryOp\Concat) {
        $left = $this->resolvePath($expr->left, $currentDir);
        $right = $this->resolvePath($expr->right, $currentDir);
        if ($left !== null && $right !== null) {
            return $left . $right;
        }
        return null;
    } else {
        // We could use ConstExprEvaluator here for more complex paths,
        // but for now let's stick to simple cases.
        return null;
    }

    if (strpos($path, '/') !== 0) {
      $path = $currentDir . '/' . $path;
    }

    return $path;
  }

  private function evaluateDatabases(Node\Expr $expr) {
    if ($expr instanceof Array_) {
      $this->databases = $this->nodeToArray($expr);
    }
  }

  private function nodeToArray(Array_ $node): array {
    $result = [];
    foreach ($node->items as $item) {
      if ($item === null) continue;
      $key = $item->key instanceof String_ ? $item->key->value : null;
      $value = null;

      if ($item->value instanceof Array_) {
        $value = $this->nodeToArray($item->value);
      } elseif ($item->value instanceof String_) {
        $value = $item->value->value;
      }

      if ($key !== null) {
        $result[$key] = $value;
      } else {
        $result[] = $value;
      }
    }

    return $result;
  }
}
