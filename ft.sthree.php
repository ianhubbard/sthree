<?php

if (!class_exists('S3')) require_once 'S3.php';

use Intervention\Image\Image;

class Fieldtype_sthree extends Fieldtype
{
  public function render()
  {
    // Check for CURL
    if (!extension_loaded('curl') && !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll')) {
      throw new Exception("ERROR: cURL extension not loaded");
    }

    // Pointless without our AWS info!
    if (!isset($this->config['awsAccessKey']) || $this->config['awsAccessKey'] == 'change-this' || $this->config['awsAccessKey'] == ''
      || !isset($this->config['awsSecretKey']) || $this->config['awsSecretKey'] == 'change-this' || $this->config['awsSecretKey'] == ''
      || !isset($this->config['awsEndPoint']) || $this->config['awsEndPoint'] == 'change-this' || $this->config['awsEndPoint'] == ''
      || !isset($this->config['awsBucket']) || $this->config['awsBucket'] == 'change-this' || $this->config['awsBucket'] == ''
      || !isset($this->config['awsDomainSuffix']) || $this->config['awsDomainSuffix'] == 'change-this' || $this->config['awsDomainSuffix'] == '') {
        throw new Exception("ERROR: AWS access information required\n\nPlease edit the configuration in sthree.yaml in the _config/addons/sthree/ directory");
    }

    $html = "<div class='file-field-container'>";

    if ($this->field_data) {
      $html .= "<div class='sthree-exists'>";
        $html .= "<img src='{$this->field_data[0]['original']}' height='58'>";
        $html .= "<div>S3 location: ".dirname($this->field_data[0]['original'])."</div>";
        $html .= "<div>File variants:";
        foreach ($this->field_data[0] as $key => $url) {
          $html .= "<div>{$key} - ".basename($url)."</div>";
        }
        $html .= "</div>";
        $html .= "<a class='btn btn-small btn-remove-sthree' href='#'>Remove</a>";
        $html .= "<input type='hidden' name='{$this->fieldname}' value='".json_encode($this->field_data)."' />";
      $html .= "</div>";
    }
    $html .= "<div class='upload-sthree".($this->field_data ? " hidden" : "")."'>";
    $html .= "<p><input type='file' name='{$this->fieldname}[file]' tabindex='{$this->tabindex}' value='' /></p>";
    foreach ($this->field_config['sizes'] as $groupname => $size_group) {
      if (count($size_group) == 1) {
        $html .= "<input type='hidden' name='{$this->fieldname}[{$groupname}]' tabindex='{$this->tabindex}' value='".key($size_group)."' />";
      } else {
        $html .= "<div>Choose the size for group ".ucfirst($groupname).":";
        foreach ($size_group as $key => $val) {
          list($name, $size) = explode(';', $val);
          $html .= "<div><input type='radio' name='{$this->fieldname}[{$groupname}]' tabindex='{$this->tabindex}' value='{$key}' /> {$name}</div>";
        }
        $html .= "</div>";
      }
    }
    $html .= "</div>";
    $html .= "</div>";

    return $html;
  }

  public function process()
  {
    $out = array();
    
    // Handle the FILES variable, checking that we have a file etc
    if (isset($_FILES['page']['name']['yaml'][$this->fieldname]['file']) && $_FILES['page']['name']['yaml'][$this->fieldname]['file'] != '') {
      $file_values = array();
      $file_values['name'] = $_FILES['page']['name']['yaml'][$this->fieldname]['file'];
      $file_values['type'] = $_FILES['page']['type']['yaml'][$this->fieldname]['file'];
      $file_values['tmp_name'] = $_FILES['page']['tmp_name']['yaml'][$this->fieldname]['file'];
      $file_values['error'] = $_FILES['page']['error']['yaml'][$this->fieldname]['file'];
      $file_values['size'] = $_FILES['page']['size']['yaml'][$this->fieldname]['file'];

      if ($file_values['error'] != '') {
        Log::error("ERROR: File upload error: ".$file_values['name']);
        return array();
      }
      if ($file_values['tmp_name'] == '') {
        Log::error("Temporary file not found: ".$file_values['tmp_name']);
        return array();
      } else {
        // Handle the uploaded file
        $upload_temp_file = $file_values['tmp_name'];

        // Check if our upload file exists
        if (!file_exists($upload_temp_file) || !is_file($upload_temp_file)) {
          Log::error("\nERROR: Uploaded file not found: $upload_temp_file\n\n");
          return array();
        }

        // Add our original file to the array for upload
        $files = array('original' => array('name' => $file_values['name'], 'file' => $upload_temp_file));
        
        // Resize the file if necessary
        if (File::isImage($upload_temp_file) && isset($this->settings['sizes'])) {
          // Get the sizes to resize to
          foreach ($this->field_data as $field => $data) {
            if ($field != 'file') {
              $size = '';
              // This should be a group - try to get the details
              $group = $this->settings['sizes'][$field];
              $size = $group[$data];
              if ($size != '') {
                // We have a size, so grab the params
                list($size_title, $size_val) = explode(';', $size);
                $exploded = explode('x', $size_val);
                if (!isset($exploded[2])) {
                  $exploded[2] = 75;
                }
                $sizes[] = array(
                  'width' => trim($exploded[0]),
                  'height' => trim($exploded[1]),
                  'quality' => trim($exploded[2]),
                  'name' => trim($field),                     // This is the name of the size group
                  'size' => trim($data)                       // This is the name of the selected size from this group
                );
              } else {
                // This appears to be an invalid field
                Log::warn('Unknown field - '.$field);
              }
            }
          }

          // Do the resizing
          foreach ($sizes as $size) {
            $image = Image::make($upload_temp_file);
            $image->resize($size['width'], $size['height'], true, true);
            $ext = File::getExtension($file_values['name']);
            // Get the base filename without the extension
            $newfile = str_replace('.'.$ext, '', $file_values['name']);
            // Add the size details and the extension to give the new filename
            $newfile .= '-'.$size['name'].'-'.$size['size'].'.'.$ext;
            // Get the temporary path for this file at this size
            $temp_image_path = sys_get_temp_dir().'/'.$newfile;

            try {
              $image->save($temp_image_path, $size['quality']);
              unset($image);
              
              // Add this file to the list to be uploaded
              $files[$size['name']] = array('name' => $newfile, 'file' => $temp_image_path);
            } catch(Exception $e) {
              Log::error('Could not write resized images. Try checking your file permissions.');
            }
          }
        }
        
        // Instantiate the class
        $s3 = new S3($this->config['awsAccessKey'], $this->config['awsSecretKey'], TRUE, $this->config['awsEndPoint']);

        // Upload our file(s)
        foreach ($files as $size => $file) {
          $prefix = (isset($this->settings['prefix']) && $this->settings['prefix'] != '' ? $this->settings['prefix'].'/' : '');
          $remote = Path::tidy($prefix.basename($file['name']));
          if ($s3->putObject($s3->inputFile($file['file']), $this->config['awsBucket'], $remote, S3::ACL_PUBLIC_READ)) {
            // Path to the uploaded file
            $out[0][$size] = Path::tidy('http://'.$this->config['awsBucket'].'.'.$this->config['awsDomainSuffix']."/".$remote);
          } else {
            $error = TRUE;
          }
          // Remove the temporary file
          unlink($file['file']);
        }
        if (isset($error) && $error == TRUE) {
          Log::error($file_values['name'].' (or one or more of its variants) could up not be uploaded to S3::'.$this->config['awsBucket']);
        }
      }
    } else {
      $out = (isset($this->field_data) && is_string($this->field_data) ? json_decode($this->field_data, TRUE) : array());
    }

    return $out;
  }
}
