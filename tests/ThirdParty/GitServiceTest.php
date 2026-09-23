<?php

namespace AKlump\Drupal\PHPUnit\Integration\ThirdParty;

use PHPUnit\Framework\TestCase;

final class GitServiceTest extends TestCase {

  public function testGetBranchNameReturnsCurrentBranch() {
    $dir = $this->createGitRepo('git-service-test-', 'feature-x');
    try {
      $this->assertSame('feature-x', (new GitService($dir))->getBranchName());
    }
    finally {
      $this->removeDirectory($dir);
    }
  }

  public function testGetBranchNameHandlesBaseDirWithSpaces() {
    $dir = $this->createGitRepo('git service test ', 'feature-y');
    try {
      $this->assertSame('feature-y', (new GitService($dir))->getBranchName());
    }
    finally {
      $this->removeDirectory($dir);
    }
  }

  public function testGetBranchNameReturnsEmptyStringWhenNotAGitRepository() {
    $dir = sys_get_temp_dir() . '/not-a-git-repo-' . uniqid();
    mkdir($dir);
    try {
      $this->assertSame('', (new GitService($dir))->getBranchName());
    }
    finally {
      $this->removeDirectory($dir);
    }
  }

  public function testGetBranchNameCachesPerBaseDir() {
    $dir = $this->createGitRepo('git-service-test-', 'cached-branch');
    try {
      $git = new GitService($dir);
      $first = $git->getBranchName();
      $second = $git->getBranchName();
      $this->assertSame($first, $second);
      $this->assertSame('cached-branch', $second);
    }
    finally {
      $this->removeDirectory($dir);
    }
  }

  private function createGitRepo(string $prefix, string $branch): string {
    $dir = sys_get_temp_dir() . '/' . $prefix . uniqid();
    mkdir($dir);
    // A branch name is unresolvable via `git rev-parse --abbrev-ref HEAD`
    // until the repository has at least one commit.
    exec(sprintf(
      'cd %s'
      . ' && git init --quiet --initial-branch=%s'
      . ' && git config user.email test@example.com'
      . ' && git config user.name test'
      . ' && git commit --quiet --allow-empty -m init'
      . ' 2>&1',
      escapeshellarg($dir),
      escapeshellarg($branch)
    ));

    return $dir;
  }

  private function removeDirectory(string $dir): void {
    exec('rm -rf ' . escapeshellarg($dir));
  }

}
