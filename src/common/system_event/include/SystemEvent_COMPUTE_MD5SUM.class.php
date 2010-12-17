<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/system_event/SystemEvent.class.php';

/**
 * Compute md5sum for frs file
 *
 */
class SystemEvent_COMPUTE_MD5SUM extends SystemEvent {

    /**
     * Set multiple logs
     *  
     * @param String $log Log string
     * 
     * @return void
     */
    public function setLog($log) {
        if (!isset($this->log) || $this->log == '') {
            $this->log = $log;
        } else {
            $this->log .= PHP_EOL.$log;
        }
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in 
     * notifications
     * 
     * @param bool $with_link true if you want links to entities. The returned 
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link) {
        $txt ='';
        list($path, $fileId) = $this->getParametersAsArray();
        $txt .= 'file: '.$fileId.' , path: '.$path;
        return $txt;
    }

    /** 
     * Process stored event
     * 
     * @return Boolean
     */
    public function process() {
        list($path, $fileId) = $this->getParametersAsArray();
        if ($fileId > 0) {
            $fileFactory = new FRSFileFactory();
            $file        = $fileFactory->getFRSFileFromDb($fileId);
            //Compute Md5sum for files
            $md5Computed = $this->computeFRSMd5Sum($path);
            if (!$md5Computed) {
                $user = $this->getUser($file->getUserID());
                $subject = $GLOBALS['sys_name'] . ' Error in '.$path;
                $body = "An error according while trying to compute md5sum in your uploaded file";
                $mail =  new Mail();
                $mail->setFrom($GLOBALS['sys_noreply']);
                $mail->setBcc($user->getEmail());
                $mail->setSubject($subject);
                $mail->setBody($body);
                if (!$mail->send()) {
                    $this->error('Could not send mail to inform user that computing md5sum failed');
                    return false;
                }
                $this->error('Computing md5sum failed ');
                return false;
            }
            // Update DB
            if (!$this->updateDB($fileId, $md5Computed)) {
                $this->error('Could not update the computed checksum for file (Filename: '.$file->getFileName().')');
                return false;
            }
            $this->done();
            return true;
        }
    }

    public function computeFRSMd5Sum($filePath) {
        return md5_file($filePath);
    }
    
    public function updateDB($fileId, $md5Computed) {
        $fileFactory = new FRSFileFactory();
        return $fileFactory->updateComputedMd5sum($fileId, $md5Computed);
    }
}

?>
