<?php

namespace TopFloor\ComposerCleanupVcsDirs;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Handler {
  /**
   * @var Composer
   */
  protected $composer;

  /**
   * @var IOInterface
   */
  protected $io;

  /**
   * Handler constructor.
   * @param \Composer\Composer $composer
   * @param \Composer\IO\IOInterface $io
   */
  public function __construct(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  /**
   * @param $parentDir
   * @param bool $excludeRoot
   * @return \Symfony\Component\Finder\Finder|\Symfony\Component\Finder\SplFileInfo[]
   */
  public function getVcsDirs($parentDir, $excludeRoot = false) {
    $finder = new Finder();

    $iterator = $finder
      ->directories()
      ->in($parentDir)
      ->ignoreVCS(false)
      ->exclude(['node_modules', '.git/*'])
      ->ignoreDotFiles(false)
      ->name('.git');

    if ($excludeRoot) {
      $iterator = $iterator->depth('> 0');
    }

    return $iterator;
  }

  /**
   * @param $parentDir
   * @param bool $excludeRoot
   */
  public function cleanupVcsDirs($parentDir, $excludeRoot = false) {
    $vcsDirs = $this->getVcsDirs($parentDir, $excludeRoot);

    foreach ($vcsDirs as $file) {
      $this->deleteVcsDir($file);
    }
  }

  /**
   * @param \Symfony\Component\Finder\SplFileInfo $file
   */
  public function deleteVcsDir(SplFileInfo $file) {
    $this->io->write("Deleting " . $file->getBasename() . " directory from " . $file->getPath());

    $fs = new Filesystem();

    $fs->removeDirectory($file->getRealPath());
  }

  /**
   * @param \Composer\Package\PackageInterface $package
   */
  public function onPostPackageEvent(PackageInterface $package) {
    $path = $this->composer->getInstallationManager()->getInstallPath($package);

    $this->cleanupVcsDirs($path);
  }
}