<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Web\Controller;

class AssetsController extends Controller
{
    protected $requiresAuthentication = false;

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();
    }

    public function indexAction()
    {
        $asset = $this->findFileInPath(
            $this->params->getRequired('asset'),
            rtrim($this->Module()->getBaseDir(), '/') . '/assets'
        );

        if ($asset === null) {
            $this->httpNotFound('Asset not found');
        }

        $this->getResponse()->setHeader(
            'Cache-Control',
            'public, max-age=31536000',
            true
        );

        // TODO(el): Account `filemtime()`?
        $eTag = hash_file('crc32c', $asset);

        if ($this->getRequest()->getServer('HTTP_IF_NONE_MATCH') === $eTag) {
            $this->getResponse()
                ->setHttpResponseCode(304);
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $asset);
            finfo_close($finfo);

            $this->getResponse()
                ->setHeader('ETag', $eTag)
                ->setHeader('Content-Type', $mimeType, true)
                ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($asset)) . ' GMT');

            readfile($asset);
        }
    }

    /**
     * Find the absolute path of a file within a specified base directory
     *
     * This function resolves the absolute path of a given filename within a base directory,
     * ensuring that the resolved path is within the base directory to prevent path traversal attacks.
     * It also checks if the file exists at the resolved path.
     *
     * @param string $filename The name of the file to find
     * @param string $baseDir The base directory to search within
     *
     * @return string|null The absolute path of the file if found and valid, or null otherwise
     */
    protected function findFileInPath(string $filename, string $baseDir): ?string
    {
        $path = realpath(rtrim($baseDir, '/') . "/$filename");

        if (
            // Check if the path resolution was successful and the file exists.
            $path !== false
            // Ensure the resolved path is within the base directory to prevent path traversal.
            && str_starts_with($path, $baseDir)
            // Verify that the file still exists at the resolved path since realpath might cache results and
            // not return false if the file is removed.
            && file_exists($path)
        ) {
            return $path;

        }

        return null;
    }
}
