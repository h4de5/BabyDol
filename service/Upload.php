<?php

namespace h4de5\BabyDol;

class Upload {

    /**
     * path to upload directory
     *
     * @var string
     */
    private $uploadPath;

    function __construct ($uploadPath) {
        $this->uploadPath = $uploadPath;
    }

    public function getUploadPath() {
        return rtrim($this->uploadPath, '/'). '/';
    }

    /**
     * checks if upload file is set in $post and $files
     *
     * @param string $formFilename
     * @param string $formFilefield
     * @return boolean
     */
    public function isUpload($formFilename, $formFilefield) {
        if(!isset($_POST[$formFilename]) || !isset($_FILES[$formFilefield])) {
            return false;
        }
        return true;
    }

    /**
     * get files via post, save it to file system
     *
     * @param string $formFilename form field
     * @param string $formFilefield
     * @return string saved filename
     */
    public function fetch($formFilename, $formFilefield) {

        if (!$this->isUpload($formFilename, $formFilefield)) {
            throw \Exception("Upload fields not found");
        }
    
        $fileName = trim($_POST[$formFilename]);
        $tempName = $_FILES[$formFilefield]['tmp_name'];

        if (empty($fileName) || empty($tempName)) {
            throw \Exception("Upload fields empty");
        }
        $filePath = $this->getUploadPath() . $fileName;
    
        // make sure that one can upload only allowed audio/video files
        $allowed = array(
            'pcm',
            'wav',
        );
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!$extension || empty($extension) || !in_array($extension, $allowed)) {
            throw \Exception("File extension not permitted");
        }
    
        if (!move_uploaded_file($tempName, $filePath)) {
            throw \Exception("Saving uploaded file failed");
        }

        return $filePath;
    }


    /**
     * concatinates files, adds file chunk to target
     *
     * @param string $chunk
     * @param string $target
     * @return string
     */
    public function concat($chunk, $targetName) {

        $targetPath = $this->getUploadPath() . $targetName;

        $handle = fopen($chunk, "r");
        $contents = fread($handle, filesize($chunk));
        fclose($handle);

        if (!$handle = fopen($targetPath, "a")) {
            throw \Exception("Could not open target file for appending");
        }

        if (!fwrite($handle, $contents)) {
            throw \Exception("Could not write to target file");
        }
        fclose($handle);

        return $targetPath;
    }

}