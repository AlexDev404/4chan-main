<?php
/**
 * Twister Captcha
 */
class TwisterCaptcha {
  private
    $font_id = 0,
    $font_width = 0,
    $font_height = 0,
    $font_scale = 1,
    $font_spacing = 0,
    
    $difficulty = null,
    
    $char_dx_ratio = 0,
    $char_dy_ratio = 0,
    $noise_dy_ratio_min = 0,
    $noise_dy_ratio_max = 0,
    
    $blotFilter = IMG_FILTER_EDGEDETECT,
    
    $useScoreLines = false,
    $useGridLines = false,
    $useInkBlot = false,
    $useEdgeBlock = false,
    $useFakeCharPadding = false,
    $useFenceNoise = false,
    $useTopBottomY = false,
    $useInvert = false,
    $useStaticRot = false,
    $useOverlayId = 0,
    $useOverlayDark = true,
    $useAltBlackWhite = false,
    $useSimplexBg = false,
    $useEmboss = false,
    $useSpecialRot = false,
    $useExtraSpaces = false,
    $useEdgeDetect = false,
    $useMeanRemoval = false
  ;
  
  const CHAR_LIST = 'ADGHJKMNPRSTVWXY0248';
  const CHAR_LIST_SPLIT = 'ADGHJKMNTVWXY4';
  /**
   * B0 = dots
   * F7 = double ~
   * 1A / 1B = arrow right / left
   * A9 / AA = L rotated right / left
   */
  const CHAR_NOISE = "\xF7\x1A\x1B\xA9\xAA+<";
  
  const CHAR_NOISE_OVERLAY = "\xB0";
  
  const IMG_WIDTH_MAX = 300;
  const IMG_HEIGHT = 80;
  
  const SHEAR_X_MIN = 3;
  const SHEAR_X_MAX = 6;
  const SHEAR_Y_MIN = 14;
  const SHEAR_Y_MAX = 30;
  
  const SCALE_X_MIN = 0;
  const SCALE_X_MAX = 80;
  const SCALE_Y_MIN = 0;
  const SCALE_Y_MAX = 15;
  
  const SCALE_MAX = 80;
  
  const CHAR_DY_RATIO = 0.10;
  
  const NOISE_DY_RATIO_MIN = 0.380;
  const NOISE_DY_RATIO_MAX = 0.460;
  
  const NOISE_OFS_X_RATIO_MIN = 1.30;
  const NOISE_OFS_X_RATIO_MAX = 1.50;
  
  const LEVEL_EASY = 1;
  const LEVEL_NORMAL = 2;
  const LEVEL_HARD = 3;
  const LEVEL_LUNATIC = 4;
  
  const CHALLENGE_ID_BYTES = 12;
  
  // 32 bytes for SHA256
  private static $hmac_secret = '2Lm9j17D2SKElS1/s5SQCzIH1E031gSfKarbLNycerA=';
  
  public function __construct($font_file) {
    $font_id = imageloadfont($font_file);
    
    if ($font_id) {
      $this->font_id = $font_id;
      $this->font_width = imagefontwidth($font_id);
      $this->font_height = imagefontheight($font_id);
      
      $this->setFontScale(0.62);
      
      $this->setDyRatios(self::CHAR_DY_RATIO, self::NOISE_DY_RATIO_MIN, self::NOISE_DY_RATIO_MAX);
      
      $this->setDifficulty(self::LEVEL_NORMAL);
    }
  }
  
  public function __call($name, $args) {
    return false;
  }
  
  public function setDifficulty($val) {
    if ($val === $this->difficulty) {
      return true;
    }
    
    if ($val === self::LEVEL_EASY) {
      $this->char_dx_ratio = 0.210;
    }
    else if ($val === self::LEVEL_NORMAL) {
      $this->char_dx_ratio = 0.218;
    }
    else if ($val === self::LEVEL_HARD) {
      $this->char_dx_ratio = 0.240;
    }
    else if ($val === self::LEVEL_LUNATIC) {
      $this->char_dx_ratio = 0.280;
    }
    else {
      return false;
    }
    
    $this->difficulty = $val;
    
    return true;
  }
  
  public function setFontScale($scale) {
    if (!$scale || $scale <= 0) {
      $scale = 0.6;
    }
    
    $this->font_scale = self::IMG_HEIGHT / ($this->font_height * (100 + self::SHEAR_Y_MAX / 2) / 100) * $scale;
  }
  
  public function setDyRatios($char, $noise_min, $noise_max) {
    $char = (float)$char;
    $noise_min = (float)$noise_min;
    $noise_max = (float)$noise_max;
    
    if ($char <= 0.0 || $noise_min <= 0.0 || $noise_max <= 0.0) {
      return false;
    }
    
    $this->char_dy_ratio = $char;
    $this->noise_dy_ratio_min = $noise_min;
    $this->noise_dy_ratio_max = $noise_max;
  }
  
  private function debugOutputImage($img) {
    header('Content-Type: image/png');
    imagesavealpha($img, true);
    imagepng($img);
    die();
  }
  
  public function useJumpyMode($flag) {
    if ($flag) {
      $this->setDyRatios(0.5, 0.42, 0.5);
    }
    else {
      $this->setDyRatios(self::CHAR_DY_RATIO, self::NOISE_DY_RATIO_MIN, self::NOISE_DY_RATIO_MAX);
    }
  }
  
  public function useTopBottomY($flag) {
    $this->useTopBottomY = (bool)$flag;
  }
  
  public function useInvert($flag) {
    $this->useInvert = (bool)$flag;
  }
  
  public function useStaticRot($flag) {
    $this->useStaticRot = (bool)$flag;
  }
  
  public function useFenceNoise($flag) {
    $this->useFenceNoise = (bool)$flag;
  }
  
  public function useNegateBlotFilter($flag) {
    if ($flag === true) {
      $this->blotFilter = IMG_FILTER_NEGATE;
    }
    else {
      $this->blotFilter = IMG_FILTER_EDGEDETECT;
    }
  }
  
  public function useFakeCharPadding($flag) {
    $this->useFakeCharPadding = (bool)$flag;
  }
  
  public function useScoreLines($flag) {
    $this->useScoreLines = (bool)$flag;
  }
  
  public function useGridLines($flag) {
    $this->useGridLines = (bool)$flag;
  }
  
  public function useInkBlot($flag) {
    $this->useInkBlot = (bool)$flag;
  }
  
  public function useOverlayId($id, $dark = true) {
    $this->useOverlayId = (int)$id;
    $this->useOverlayDark = (bool)$dark;
  }
  
  public function useAltBlackWhite($flag) {
    $this->useAltBlackWhite = (bool)$flag;
  }
  
  public function useSimplexBg($flag) {
    $this->useSimplexBg = (bool)$flag;
  }
  
  public function useEmboss($flag) {
    $this->useEmboss = (bool)$flag;
  }
  
  public function useSpecialRot($flag) {
    $this->useSpecialRot = (bool)$flag;
  }
  
  public function useExtraSpaces($flag) {
    $this->useExtraSpaces = (bool)$flag;
  }
  
  public function useEdgeDetect($flag) {
    $this->useEdgeDetect = (bool)$flag;
  }
  
  public function useMeanRemoval($flag) {
    $this->useMeanRemoval = (bool)$flag;
  }
  
  public function useEdgeBlock($flag) {
    $this->useEdgeBlock = (bool)$flag;
    
    if ($this->useEdgeBlock) {
      $this->char_dx_ratio = 0.180;
    }
    else {
      $this->setDifficulty($this->difficulty);
    }
  }
  
  private function getRandomString($len) {
    $chars_lim = strlen(self::CHAR_LIST) - 1;
    
    $str = [];
    
    for ($i = 0; $i < $len; $i++) {
      $str[] = self::CHAR_LIST[mt_rand(0, $chars_lim)];
    }
    
    return implode('', $str);
  }
  
  private function getRandomStringPair($len, $offset = 1) {
    $chars_lim = strlen(self::CHAR_LIST) - 1;
    
    $str = [];
    $strike = [];
    
    for ($i = 0; $i < $len; $i++) {
      $k = mt_rand(0, $chars_lim);
      
      $str[] = self::CHAR_LIST[$k];
      
      $k += $offset;
      
      if ($k > $chars_lim) {
        $k = 0;
      }
      else if ($k < 0) {
        $k = $char_lim;
      }
      
      $strike[] = self::CHAR_LIST[$k];
    }
    
    return [ implode('', $str), implode('', $strike) ];
  }
  
  private function scaleImage($img, $w, $h) {
    $img_scaled = imagescale($img, $w, $h, IMG_NEAREST_NEIGHBOUR);
    return $img_scaled;
  }
  
  private function getScaleMatrix($x, $y) {
    return imageaffinematrixget(IMG_AFFINE_SCALE, [$x, $y]);
  }
  
  private function getShearHMatrix($a) {
    return imageaffinematrixget(IMG_AFFINE_SHEAR_HORIZONTAL, $a);
  }
  
  private function getShearVMatrix($a) {
    return imageaffinematrixget(IMG_AFFINE_SHEAR_VERTICAL, $a);
  }
  
  private function getRotMatrix($a) {
    return imageaffinematrixget(IMG_AFFINE_ROTATE, $a);
  }
  
  private function transformImage($img, $mat, $mat2 = null, $mat3 = null, $mat4 = null) {
    if ($mat2) {
      $mat = imageaffinematrixconcat($mat, $mat2);
    }
    
    if ($mat3) {
      $mat = imageaffinematrixconcat($mat, $mat3);
    }
    
    if ($mat4) {
      $mat = imageaffinematrixconcat($mat, $mat4);
    }
    
    $w = imagesx($img);
    $h = imagesy($img);
    
    $clip = ['x' => 0, 'y' => 0, 'width' => $w, 'height' => $h];
    
    imagesetinterpolation($img, IMG_NEAREST_NEIGHBOUR);
    
    $img = imageaffine($img, $mat, $clip);
    
    return $img;
  }
  
  private function warpImage($img, $scale_x, $rotate) {
    $shear_x = 
      [ 1, 0, (self::SHEAR_X_MIN + mt_rand(0, self::SHEAR_X_MAX)) * (mt_rand(0, 1) ? 1 : -1) / 100,
        1, 0, 0 ]
    ;
    
    $shear_y = 
      [ 1, (self::SHEAR_Y_MIN + mt_rand(0, self::SHEAR_Y_MAX)) * (mt_rand(0, 1) ? 1 : -1) / 100, 0,
        1, 0, 0 ]
    ;
    
    $scale = 
      [ $scale_x, 0, 0,
        1.0 + (mt_rand(self::SCALE_Y_MIN, self::SCALE_Y_MAX) / 100), 0, 0 ]
    ;
    
    if ($rotate) {
      if ($this->difficulty > TwisterCaptcha::LEVEL_HARD) {
        $da = 15;
      }
      else if ($this->difficulty > TwisterCaptcha::LEVEL_NORMAL) {
        $da = 11;
      }
      else {
        $da = 6;
      }
      
      $angle = mt_rand(-$da, $da) * M_PI / 180;
      
      $cos = cos($angle);
      $sin = sin($angle);
      
      $rot = [ $cos, -$sin, $sin, $cos, 0, 0 ];
      
      $mat = imageaffinematrixconcat($scale, $rot);
      $mat = imageaffinematrixconcat($mat, $shear_x);
    }
    else {
      $mat = imageaffinematrixconcat($scale, $shear_x);
    }
    
    $mat = imageaffinematrixconcat($mat, $shear_y);
    
    $img_warped = imageaffine($img, $mat);
    
    return [$img_warped, $scale_x];
  }
  
  private function cleanImageAlpha($img, $keep_true_color = false) {
    imagetruecolortopalette($img, false, 2);
    if ($keep_true_color) {
      imagepalettetotruecolor($img);
    }
    else {
      imagecolorset($img, 0, 0, 0, 0);
      imagecolorset($img, 1, 238, 238, 238);
    }
  }
  
  private function mergeCharImage($dest_img, $char_img, $dest_x, $dest_y, $clr, $alpha) {
    $w = imagesx($char_img);
    $h = imagesy($char_img);
    
    imagealphablending($dest_img, true);
    
    $txt_clr = imagecolorallocatealpha($dest_img, $clr, $clr, $clr, $alpha);
    
    for ($x = 0; $x < $w; ++$x) {
      for ($y = 0; $y < $h; ++$y) {
        $c = imagecolorat($char_img, $x, $y);
        
        $a = ($c >> 24) & 0xFF;
        $b = $c & 0xFF;
        
        if ($a < 1 && $b < 200) {
          imagesetpixel($dest_img, $dest_x + $x, $dest_y + $y, $txt_clr);
        }
      }
    }
    
    return true;
  }
  
  private function flattenImageAlpha($img) {
    imagetruecolortopalette($img, false, 3);
    
    for ($i = 0; $i < 3; $i++) { 
      $c = imagecolorsforindex($img, $i);
      
      if ($c['red'] < 128 && $c['green'] > 200) {
        imagecolortransparent($img, $i);
      }
      else if ($c['blue'] > 140) {
        imagecolorset($img, $i, 238, 238, 238);
      }
      else {
        imagecolorset($img, $i, 0, 0, 0);
      }
    }
  }
  
  private function prepareCharImage() {
    $img_char = imagecreate($this->font_width, $this->font_height);
    $img_char_clr_txt = imagecolorallocate($img_char, 0, 0, 0);
    $img_char_clr_bg = imagecolorallocate($img_char, 0, 255, 0);
    imagecolortransparent($img_char, $img_char_clr_bg);
    
    return [$img_char, $img_char_clr_txt, $img_char_clr_bg];
  }
  
  private function getCharImage($char, $height) {
    if ($height < 1) {
      return false;
    }
    
    $w = $this->font_width;
    $h = $this->font_height;
    
    // Create image
    $img = imagecreate($w, $h);
    $clr_bg = imagecolorallocate($img, 255, 255, 255);
    $clr_txt = imagecolorallocate($img, 0, 0, 0);
    imagealphablending($img, false);
    imagefilledrectangle($img, 0, 0, $w, $h, $clr_bg);
    
    // Draw character
    imagechar($img, $this->font_id, 1, 1, $char, $clr_txt);
    
    // Crop
    if ($char !== ' ') {
      $cropped = imagecropauto($img, IMG_CROP_WHITE);
      
      if ($cropped !== false) {
        $img = $cropped;
      }
    }
    
    // Resize
    $_r = imagesx($img) / imagesy($img);
    
    if ($_r <= 0) {
      return false;
    }
    
    $width = round($height * $_r);
    
    if ($width < 1) {
      return false;
    }
    //$this->debugOutputImage($img);
    
    $img = imagescale($img, $width, $height, IMG_NEAREST_NEIGHBOUR);
    
    return [$img, $width, $height];
  }
  
  private function drawCharFake($img, $img_char, $x, $y, $w, $h, $bg_clr, $char_clr) {
    $lim = strlen(self::CHAR_LIST_SPLIT) - 1;
    $char = self::CHAR_LIST_SPLIT[mt_rand(0, $lim)];
    
    $scale_x = 1.0 + (mt_rand(self::SCALE_X_MIN, self::SCALE_X_MAX) / 100);
    
    imagefilledrectangle($img_char, 0, 0, $this->font_width, $this->font_height, $bg_clr);
    imagechar($img_char, $this->font_id, 0, 0, $char, $char_clr);
    $img_scaled = $this->scaleImage($img_char, $w, $h);
    list($img_warped, $scale_x) = $this->warpImage($img_scaled, $scale_x, true);
    imagedestroy($img_scaled);
    $warped_width = imagesx($img_warped);
    $warped_height = imagesy($img_warped);
    $half_w_a = ceil($warped_width * 0.45);
    imagecopy($img, $img_warped,
      $x, $y,
      0, 0, $half_w_a, $warped_height
    );
    
    return round($w * $scale_x);
  }
  
  private function drawChar($img, $img_char, $char, $x, $y, $w, $h, $bg_clr, $char_clr,
    $scale_x = null, $rotate = false
  ) {
    imagefilledrectangle($img_char, 0, 0, $this->font_width, $this->font_height, $bg_clr);
    imagechar($img_char, $this->font_id, 0, 0, $char, $char_clr);
    
    if (!$scale_x) {
      $scale_x = 1.0 + (mt_rand(self::SCALE_X_MIN, self::SCALE_X_MAX) / 100);
    }
    else {
      $scale_x += mt_rand(0, self::SCALE_MAX) / 100;
    }
    
    $img_scaled = $this->scaleImage($img_char, $w, $h);
    
    list($img_warped, $scale_x) = $this->warpImage($img_scaled, $scale_x, $rotate);
    imagedestroy($img_scaled);
    
    $warped_width = imagesx($img_warped);
    $warped_height = imagesy($img_warped);
    $warped_delta_x = round(($warped_width - $w) / 2.0);
    
    imagecopy($img, $img_warped,
      $x, $y,
      0, 0, $warped_width, $warped_height
    );
    
    imagedestroy($img_warped);
    
    return round($w * $scale_x);
  }
  
  private function addNoise($img, $img_width, $img_height) {
    list($img_char, $img_char_clr_txt, $img_char_clr_bg) = $this->prepareCharImage();
    
    $char_width_scaled = round($this->font_width * $this->font_scale);
    $char_height_scaled = round($this->font_height * $this->font_scale);
    
    $noise_lim = strlen(self::CHAR_NOISE) - 1;
    
    $noise_dx = ceil($char_width_scaled * $this->char_dx_ratio);
    
    $noise_dy_min = floor($img_height * $this->noise_dy_ratio_min);
    $noise_dy_max = ceil($img_height * $this->noise_dy_ratio_max);
    
    $offset_x_min = round($char_width_scaled * self::NOISE_OFS_X_RATIO_MIN);
    $offset_x_max = round($char_width_scaled * self::NOISE_OFS_X_RATIO_MAX);
    
    $x = mt_rand($noise_dx, $char_width_scaled);
    $y = floor(($img_height - $char_height_scaled) / 2.0);
    
    $img_end = $img_width - $char_width_scaled;
    
    $y_flag = mt_rand(0, 1) ? true : false;
    
    while ($x < $img_end) {
      $this->drawChar($img, $img_char,
        self::CHAR_NOISE[mt_rand(0, $noise_lim)],
        $x + mt_rand(0, $noise_dx),
        $y + (mt_rand($noise_dy_min, $noise_dy_max)) * ($y_flag ? 1 : -1),
        $char_width_scaled, $char_height_scaled,
        $img_char_clr_bg, $img_char_clr_txt
      );
      
      $y_flag = !$y_flag;
      
      $x += mt_rand($offset_x_min, $offset_x_max);
    }
  }
  
  private function addLinesV($img, $img_width, $img_height, $line_count, $color) {
    $base_dx = (int)($img_width / ($line_count + 1));
    $min_dx = (int)($base_dx * 0.75);
    $max_dx = (int)($base_dx * 1.25);
    
    $min_dy = (int)($img_height / 5);
    $max_dy = (int)($img_height / 3);
    
    $x = mt_rand($base_dx, $max_dx);
    
    for ($i = 0; $i < $line_count; ++$i) {
      $this_x = $x + mt_rand($min_dx, $max_dx);
      
      $y1 = mt_rand($min_dy, $max_dy);
      $y2 = $img_height - mt_rand($min_dy, $max_dy);
      
      imageline($img,
        $this_x, $y1,
        $this_x, $y2,
        $color
      );
      
      $x += $base_dx;
    }
  }
  
  private function addPowder($img, $img_width, $img_height, $ratio, $color) {
    if ($this->difficulty > self::LEVEL_HARD) {
      $powder_max_width = 8;
    }
    else {
      $powder_max_width = 4;
    }
    
    $step = ceil(1 / $ratio);
    
    for ($x = 1; $x < $img_width; $x += $step) {
      for ($y = 1; $y < $img_height; $y += $step) {
        $x1 = mt_rand($x, $x + $step);
        $y1 = mt_rand($y, $y + $step);
        $size = mt_rand(1, $powder_max_width);
        imagefilledarc($img, $x1, $y1, $size, $size, 0, mt_rand(180, 360), $color, IMG_ARC_PIE);
      }
    }
  }
  
  private function addLines($img, $img_width, $img_height, $color) {
    $count = 5;
    
    $spacing = (int)($img_width / $count);
    $max_dx = (int)($img_width / 3);
    $mid_dx = ceil($max_dx / 2);
    
    $x = mt_rand(0, $max_dx);
    
    for ($i = 0; $i < $count; ++$i) {
      $dx = mt_rand(0, $max_dx);
      
      $x2 = $x + $dx;
      
      if ($dx >= $mid_dx) {
        imageline($img, $x, 0, $x2, $img_height, $color);
      }
      else {
        imageline($img, $x, $img_height, $x2, 0, $color);
      }
      
      $x += $spacing;
    }
  }
  
  private function addArc($img, $img_width, $img_height, $thick, $color) {
    $dx_min = 0;
    $dx_max = (int)($img_width * 0.35);
    
    $dy_min = $img_height;
    $dy_max = (int)($img_height * 1.8);
    
    $width_min = (int)($img_width) * 1.2;
    $width_max = (int)($img_width * 2.2);
    
    $start_x = (int)($img_width * 0.5);
    $start_y = (int)($img_height * 0.5);
    
    $alt = mt_rand(0, 1) === 1;
    
    $dy = mt_rand($dy_min, $dy_max);
    
    $x = $start_x + mt_rand($dx_min, $dx_max) * (mt_rand(0, 1) ? 1 : -1);
    
    if ($alt) {
      $y = $start_y - $dy;
    }
    else {
      $y = $start_y + $dy;
    }
    
    $alt = !$alt;
    
    $w = mt_rand($width_min, $width_max);
    $h = mt_rand((int)($dy * 1.7), (int)($dy * 2.3));
    
    imageellipse($img, $x, $y, $w, $h, $color);
    
    if ($thick) {
      imageellipse($img, $x, $y + 1, $w, $h, $color);
    }
  }
  
  private function addEdgeBlock($img, $img_width, $img_height) {
    $w = mt_rand(floor($img_width * 0.25), floor($img_width * 0.65));
    $x = mt_rand(0, $img_width - $w);
    //$w = $img_width;
    //$x = 0;
    
    $img_blot = imagecreatetruecolor($img_width, $img_height);
    imagecopy($img_blot, $img, 0, 0, 0, 0, $img_width, $img_height);
    imagefilter($img_blot, IMG_FILTER_EDGEDETECT);
    imagefilter($img_blot, IMG_FILTER_GAUSSIAN_BLUR);
    
    imagetruecolortopalette($img_blot, false, 8);
    
    for ($i = 0; $i < 8; $i++) { 
      $_c = imagecolorsforindex($img_blot, $i);
      
      if ($_c['green'] > 140) {
        imagecolorset($img_blot, $i, 0, 0, 0);
      }
      else {
        imagecolorset($img_blot, $i, 238, 238, 238);
      }
    }
    
    imagecopy($img, $img_blot, $x, 0, $x, 0, $w, $img_height);
    
    imagedestroy($img_blot);
    
    return [$x, $w];
  }
  
  private function addInkBlot($img, $img_width, $img_height) {
    $w = mt_rand(floor($img_width * 0.25), floor($img_width * 0.30));
    $x = mt_rand(0, $img_width - $w);
    $c_h = mt_rand(floor($img_height * 0.5), $img_height);
    
    $img_blot = imagecreatetruecolor($w, $img_height);
    imagecopy($img_blot, $img, 0, 0, $x, 0, $w, $img_height);
    imagefilter($img_blot, $this->blotFilter);
    
    $mask = imagecreatetruecolor($w, $img_height);
    $mask_green = imagecolorallocate($mask, 0, 255, 0);
    imagecolortransparent($mask, $mask_green);
    imagecopy($mask, $img, 0, 0, $x, 0, $w, $img_height);
    imagefilledellipse($mask, floor($w / 2), floor($img_height / 2), $w, $c_h, $mask_green);
    imagecopy($img_blot, $mask, 0, 0, 0, 0, $w, $img_height);
    
    imagecopy($img, $img_blot, $x, 0, 0, 0, $w, $img_height);
    
    imagedestroy($mask);
    imagedestroy($img_blot);
    
    return [$x, $w];
  }
  
  private function addScoreLines($img, $img_width, $img_height, $color) {
    $step = 10;
    
    $count = 10 + mt_rand(-2, 2);
    
    $x = mt_rand(floor($img_width * 0.10), floor($img_width * 0.20));
    $y = mt_rand(floor($img_height * 0.05), floor($img_height * 0.15));
    $l = mt_rand(floor($img_width * 0.60), floor($img_width * 0.70));
    
    for ($i = 0; $i < $count; $i++) {
      $j_x = mt_rand(-2, 2);
      $j_l = mt_rand(-2, 2);
      $y_j = mt_rand(-3, 3);
      imageline($img, $x + $j_x, $y, $x + $l + $j_l, $y + $y_j, $color);
      $y += $step;
    }
  }
  
  private function addGridLines($img, $img_width, $img_height, $color, $grid_size = 15, $line_width = 1) {
    if ($line_width > 1) {
      imagesetthickness($img, $line_width);
    }
    
    for ($x = mt_rand(2, $grid_size); $x < $img_width; $x += $grid_size) {
      imageline($img, $x + mt_rand(-1, 1), 0, $x + mt_rand(-1, 1), $img_height, $color);
    }
    
    for ($y = mt_rand(2, $grid_size); $y < $img_height; $y += $grid_size) {
      imageline($img, 0, $y + mt_rand(-1, 1), $img_width, $y + mt_rand(-1, 1), $color);
    }
    
    if ($line_width > 1) {
      imagesetthickness($img, 1);
    }
  }
  
  private function generateImage($str, $str_len) {
    if (!$this->font_id) {
      return false;
    }
    
    $char_width_scaled = round($this->font_width * $this->font_scale);
    $char_height_scaled = round($this->font_height * $this->font_scale);
    
    $char_dx = ceil($char_width_scaled * $this->char_dx_ratio);
    $char_dy = ceil($char_height_scaled * $this->char_dy_ratio);
    
    $max_possible_chars = ceil(self::IMG_WIDTH_MAX /
      ($char_width_scaled * (self::SCALE_X_MAX / 100 + 1) + $char_dx)
    );
    
    $pad_pool = $max_possible_chars - $str_len;
    /*
    if ($str_len > $max_possible_chars) {
      $ratio = $this->font_width / $this->font_height;
      $char_width_scaled = round(self::IMG_WIDTH_MAX / (self::SCALE_X_MAX / 100 + 1) / $str_len);
      $char_height_scaled = round($char_width_scaled / $ratio);
      $pad_pool = 0;
    }
    else {
      $pad_pool = $max_possible_chars - $str_len;
    }
    */ 
    $noise_lim = strlen(self::CHAR_NOISE) - 1;
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $x = mt_rand(0, $char_dx);
    
    if ($this->useTopBottomY) {
      $y = 0;
      $y_bot = $img_height - $char_height_scaled;
    }
    else {
      $y = $y_bot = ceil((($img_height - $char_height_scaled) / 2.0) - $char_height_scaled * 0.02);
    }
    
    $y_flag = (bool)mt_rand(0, 1);
    
    // Main image
    $img = imagecreatetruecolor($img_width, $img_height);
    $img_clr_bg = imagecolorallocate($img, 255, 255, 255);
    $img_clr_transp = imagecolorallocatealpha($img, 0, 255, 0, 127);
    
    imagealphablending($img, true);
    imagefill($img, 0, 0, $img_clr_bg);
    
    // Character image
    list($img_char, $img_char_clr_txt, $img_char_clr_bg) = $this->prepareCharImage();
    
    // Left padding
    $j = 0;
    
    while ($j < $pad_pool) {
      if (mt_rand(0, 1)) {
        $pad_pool--;
        
        $_y = $y + mt_rand(-$char_dy, $char_dy);
        
        if ($this->useFakeCharPadding) {
          $warped_width = $this->drawCharFake($img, $img_char,
            $x,
            $_y,
            $char_width_scaled, $char_height_scaled,
            $img_char_clr_bg, $img_char_clr_txt
          );
        }
        else {
          $warped_width = $this->drawChar($img, $img_char,
            self::CHAR_NOISE[mt_rand(0, $noise_lim)],
            $x,
            $_y,
            $char_width_scaled, $char_height_scaled,
            $img_char_clr_bg, $img_char_clr_txt
          );
        }
        
        $x += $warped_width;
      }
      else {
        $j++;
      }
    }

    // Characters
    $scale_x = 0;
    $scale_adjust = false;
    $scale_x_mid = (int)(self::SCALE_X_MAX - abs(self::SCALE_X_MIN)) / 2;
    
    $chars_start_x = $x;
    
    if ($this->difficulty > self::LEVEL_EASY && $str_len > 1) {
      $inter_pad_idx = mt_rand(1, $str_len - 1);
    }
    else {
      $inter_pad_idx = -1;
    }
    
    $bot_slot = mt_rand(0, $str_len - 1);
    
    for ($i = 0; $i < $str_len; $i++) {
      if ($i === $inter_pad_idx) {
        $x += $char_dx;
      }
      
      $this_y = $y;
      
      if ($this->useTopBottomY && $bot_slot === $i) {
        $this_y = $y_bot;
      }
      
      if ($scale_adjust) {
        if ($scale_x >= $scale_x_mid) {
          $scale_x = mt_rand(self::SCALE_X_MIN, $scale_x_mid);
          $scale_adjust = false;
        }
        else {
          $scale_x = mt_rand($scale_x_mid, self::SCALE_X_MAX);
          $scale_adjust = false;
        }
      }
      else {
        $scale_x = mt_rand(self::SCALE_X_MIN, self::SCALE_X_MAX);
        $scale_adjust = true;
      }
      
      $scale_x_f = 1.0 + ($scale_x / 100);
      
      $warped_width = $this->drawChar($img, $img_char,
        $str[$i],
        $x,
        $this_y + mt_rand(0, $char_dy) * ($y_flag ? 1 : -1),
        $char_width_scaled, $char_height_scaled,
        $img_char_clr_bg, $img_char_clr_txt, $scale_x_f, true
      );
      
      $y_flag = !$y_flag;
      
      $x += ($warped_width - $char_dx);
    }
    
    // Right padding
    $j = 0;
    
    while ($j < $pad_pool) {
      $pad_pool--;
      
      $_y = $y + mt_rand(-$char_dy, $char_dy);
      
      if ($this->useFakeCharPadding) {
        $warped_width = $this->drawCharFake($img, $img_char,
          $x,
          $_y,
          $char_width_scaled, $char_height_scaled,
          $img_char_clr_bg, $img_char_clr_txt
        );
      }
      else {
        $warped_width = $this->drawChar($img, $img_char,
          self::CHAR_NOISE[mt_rand(0, $noise_lim)],
          $x,
          $_y,
          $char_width_scaled, $char_height_scaled,
          $img_char_clr_bg, $img_char_clr_txt
        );
      }
      
      $x += $warped_width;
    }
    
    return [ $img, $img_width, $img_height, $img_clr_bg ];
  }
  
  private function generateBgImage($img_width) {
    if (!$this->font_id) {
      return false;
    }
    
    $char_width_scaled = round($this->font_width * $this->font_scale);
    $char_height_scaled = round($this->font_height * $this->font_scale);
    
    $char_dy = ceil($char_height_scaled * $this->char_dy_ratio);
    
    $img_height = self::IMG_HEIGHT;
    
    $_dx = ceil($char_width_scaled * $this->char_dx_ratio);
    $x = mt_rand($_dx, $char_width_scaled - $_dx);
    $y = floor((($img_height - $char_height_scaled) / 2.0) + $char_height_scaled * 0.025);
    $y_flag = (bool)mt_rand(0, 1);
    
    // Main image
    $img = imagecreatetruecolor($img_width, $img_height);
    $img_clr_bg = imagecolorallocate($img, 255, 255, 255);
    
    imagefilledrectangle($img, 0, 0, $img_width, $img_height, $img_clr_bg);
    
    // Character image
    list($img_char, $img_char_clr_txt, $img_char_clr_bg) = $this->prepareCharImage();
    
    $char_lim = strlen(self::CHAR_LIST) - 1;
    $x_lim = $img_width - $char_width_scaled;
    
    while ($x < $x_lim) {
      $warped_width = $this->drawChar($img, $img_char,
        self::CHAR_LIST[mt_rand(0, $char_lim)],
        $x,
        $y + $char_dy * ($y_flag ? 1 : -1),
        $char_width_scaled, $char_height_scaled,
        $img_char_clr_bg, $img_char_clr_txt
      );
      
      // ---
      
      $y_flag = !$y_flag;
      
      $x += $warped_width - mt_rand((int)($warped_width * 0.1), (int)($warped_width * 0.5));
    }
    
    return [ $img, $img_width, $img_height, $img_clr_bg ];
  }
  
  private function addFenceNoise($img, $img_width, $img_height, $img_clr_bg) {
    //$this->addPowder($img, $img_width, $img_height, 0.15, $img_clr_bg);
    $this->addGridLines($img, $img_width, $img_height, $img_clr_bg);
  }
  
  private function sliceVertical($img, $img_bg, $img_width, $img_height, $bg_width) {
    $img_clr_bg = imagecolorallocatealpha($img, 0, 255, 0, 127);
   
    $char_width = round($this->font_width * $this->font_scale);
    $char_height = round($this->font_height * $this->font_scale);
    
    $slice_width_min = floor($char_width * 0.8);
    $slice_width_max = ceil($char_width * 1.0);
    
    $slice_height_min = floor($img_height * 0.7);
    $slice_height_max = ceil($img_height * 0.9);
    
    $slice_dx = floor($slice_width_min / 10);
    $slice_dy = floor($char_height / 10);
    
    $slice_offset = $slice_width_min * 3;
    
    $img_start = $char_width + (int)($slice_width_min / 2);
    $img_end = $img_width - $char_width - 1;
    
    $fence_size = $img_end - $img_start;
        
    $bg_start = $img_start;
    $bg_end = $bg_width - $char_width - 1;
    
    $bg_fence_start = $bg_start + mt_rand(0, $bg_end - $fence_size - $bg_start);
    $bg_fence_end = $bg_fence_start + $fence_size - 1;
    
    $img_x = $img_start;
    $bg_x = $bg_fence_start;
    
    $slices = [];
    
    while ($img_x < $img_end) {
      $sw = mt_rand($slice_width_min, $slice_width_max);
      $sh = mt_rand($slice_height_min, $slice_height_max);
      
      $dx = mt_rand(-$slice_dx, $slice_dx);
      $sy = $img_height - $sh;
      
      imagecopy($img_bg, $img, $bg_x + $dx, $sy, $img_x + $dx, $sy, $sw, $sh);
      
      $slices[] = [ $img_x + $dx, $sy, $sw, $sh, $img_x + $sw - 2, $sh - 2 ];
      
      $img_x += $slice_offset;
      $bg_x += $slice_offset;
    }
    
    // Dummy fence
    $slice_lim = count($slices) - 1;
    
    $dummy_end = $bg_fence_start - $slice_width_min - 1;
    $dummy_x = $slice_width_min;
    
    while ($dummy_x < $dummy_end) {
      list($_x, $_y, $_w, $_h) = $slices[mt_rand(0, $slice_lim)];
      imagecopy($img_bg, $img, $dummy_x, $_y, $_x, $_y, $_w, $_h);
      $dummy_x += $slice_offset;
    }
    
    $dummy_end = $bg_end + $slice_width_min - 1;
    $dummy_x = $bg_fence_end + $slice_width_min;
    
    while ($dummy_x < $dummy_end) {
      list($_x, $_y, $_w, $_h) = $slices[mt_rand(0, $slice_lim)];
      imagecopy($img_bg, $img, $dummy_x, $_y, $_x, $_y, $_w, $_h);
      $dummy_x += $slice_offset;
    }
    
    // Add noise
    if ($this->difficulty > self::LEVEL_EASY && !$this->useTopBottomY) {
      $this->addNoise($img, $img_width, $img_height);
    }
    
    // Clear fence
    imagealphablending($img, false);
    
    foreach ($slices as $rect) {
      $width = $rect[4] - $rect[0];
      $height = $rect[5] - $rect[1];
      $x = $rect[0] + (int)($width / 2);
      $y = $rect[1] + (int)($height / 2);
      
      imagefilledrectangle($img,
        $rect[0], $rect[1],
        $rect[0] + $rect[2] - 1, $rect[1] + $rect[3] - 1,
      $img_clr_bg);
    }
    
    // Add transparent noise
    if ($this->useFenceNoise) {
      $this->addFenceNoise($img_bg, $bg_width, $img_height, 1);
    }
    
    imagealphablending($img, true);
    
    return $bg_fence_start - $bg_start;
  }
  
  private function sliceVerticalFog($img, $img_bg, $img_chars, $slide_range = 60) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $slice_width = ceil($this->font_width * $this->font_scale);
    
    if ($slice_width <= 0) {
      return false;
    }
    
    $this->removeBackground($img_chars);
    
    // Temp image containing background chars
    $img_chars_tmp = imagecreate($img_width, $img_height);
    imagepalettecopy($img_chars_tmp, $img_chars);
    imagecolortransparent($img_chars_tmp, 1);
    imagealphablending($img_chars_tmp, false);
    imagefilledrectangle($img_chars_tmp, 0, 0, $img_width, $img_height, 1);
    imagealphablending($img_chars_tmp, true);
    
    // Copy and clear 2 slices
    $_qw = ceil($img_width * 0.25);
    $slice_dx = mt_rand(0, $slide_range);
    $slices_x = [ $_qw - $slice_width, $img_width - $_qw - $slice_width ];
    
    $c = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagecolortransparent($img, $c);
    imagesavealpha($img, true);
    imagealphablending($img, false);
    
    $slice_mini_width = ceil($slice_width * 0.33);
    
    foreach ($slices_x as $x) {
      imagecopy($img_chars_tmp, $img_chars, $x + $slice_dx, 0, $x, 0, $slice_width, $img_height);
      
      for ($_i = 1; $_i < 4; $_i++) {
        $_x = $x + $slice_dx - $slice_mini_width * $_i;
        imagecopy($img_chars_tmp, $img_chars, $_x, 0, $x, 0, $slice_mini_width, $img_height);
        $_x = $x + $slice_dx + $slice_width + $slice_mini_width * $_i;
        imagecopy($img_chars_tmp, $img_chars, $_x, 0, $x + $slice_width - $slice_mini_width, 0, $slice_mini_width, $img_height);
      }
      
      imagefilledrectangle($img, $x, 0, $x + $slice_width - 1, $img_height - 1, $c);
    }
    
    //$this->addGridLines($img_chars_tmp, $img_width, $img_height, 0, mt_rand(15, 20));
    //$this->addPowder($img_chars_tmp, $img_width, $img_height, 0.15, 0);
    
    // Copy characters to background
    imagecolorset($img_chars_tmp, 0, 0, 0, 0, mt_rand(64, 72));
    imagealphablending($img_bg, true);
    imagecopy($img_bg, $img_chars_tmp, 0, 0, 0, 0, $img_width, $img_height);
    
    if ($this->useInvert) {
      imagefilter($img_bg, IMG_FILTER_NEGATE);
    }
    
    imagealphablending($img, true);
    
    return $slide_range;
  }
  
  private function sliceVerticalSimplex($img, $img_bg, $img_chars, $slide_range = 60) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $slice_width = ceil($this->font_width * $this->font_scale);
    
    if ($slice_width <= 0) {
      return false;
    }
    
    // Copy and clear 2 slices
    $_qw = ceil($img_width * 0.25);
    $slice_dx = mt_rand(0, $slide_range);
    $slices_x = [ $_qw - $slice_width, $img_width - $_qw - $slice_width ];
    
    $c = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagecolortransparent($img, $c);
    imagesavealpha($img, true);
    imagealphablending($img, false);
    
    imagealphablending($img_bg, true);
    
    $slice_mini_width = ceil($slice_width * 0.33);
    
    foreach ($slices_x as $x) {
      imagecopy($img_bg, $img_chars, $x + $slice_dx, 0, $x, 0, $slice_width, $img_height);
      
      for ($_i = 1; $_i < 4; $_i++) {
        $_x = $x + $slice_dx - $slice_mini_width * $_i;
        imagecopy($img_bg, $img_chars, $_x, 0, $x, 0, $slice_mini_width, $img_height);
        $_x = $x + $slice_dx + $slice_width + $slice_mini_width * $_i;
        imagecopy($img_bg, $img_chars, $_x, 0, $x + $slice_width - $slice_mini_width, 0, $slice_mini_width, $img_height);
      }
      
      imagefilledrectangle($img, $x, 0, $x + $slice_width - 1, $img_height - 1, $c);
    }
    
    // Noise
    $c = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(85, 95));
    //$this->addGridLines($img_bg, $img_width, $img_height, $c, 30, 2);
    $this->addPowder($img_bg, $img_width, $img_height, 0.07, $c);
    
    if ($this->useInvert) {
      imagefilter($img_bg, IMG_FILTER_NEGATE);
    }
    
    imagealphablending($img, true);
    
    return $slide_range;
  }
  
  public function generateTwisterV($char_count) {
    if (!$this->font_id) {
      return false;
    }
    
    if ($char_count > 5) {
      $char_count = 5;
    }
    
    $challenge_str = $this->getRandomString($char_count);
    
    $this->setFontScale(0.96);
    
    list($img, $img_width, $img_height, $img_white) = $this->generateImage($challenge_str, $char_count);
    list($img_bg, $bg_width, $bg_height, $img_bg_white) = $this->generateBgImage($img_width + 59);
    
    $img_clr_bg = imagecolorallocatealpha($img, 0, 255, 0, 127);
    
    $clear_x = mt_rand(0, $bg_width - $img_width);
    $clear_h = floor($img_height * 0.4);
    $clear_y = $img_height - $clear_h;
    
    imagefilledrectangle($img_bg, 0, $clear_y, $bg_width, $img_height, $img_bg_white);
    imagecopy($img_bg, $img, $clear_x, $clear_y, 0, $clear_y, $img_width, $clear_h);
    imagefilledrectangle($img_bg, 0, $clear_y, $bg_width, $clear_y + 0, 0);
    
    imagefilter($img, IMG_FILTER_EDGEDETECT);
    
    // Rays
    $ray_count = mt_rand(18, 20);
    $spacing = ceil($img_width / ($ray_count + 1));
    $dx_max = 5;
    
    $x = mt_rand(0, $spacing);
    
    imagesetthickness($img, 2);
    
    for ($i = 0; $i < $ray_count; ++$i) {
      $x1 = $x;
      $y1 = 0;
      
      $to_x = $x1 + mt_rand(-$dx_max, $dx_max);
      
      for ($to_y = 0; $to_y < $clear_y; $to_y++) {
        $_c1 = imagecolorat($img, $to_x, $to_y);
        $_c2 = imagecolorat($img, $to_x + 1, $to_y);
        $_c3 = imagecolorat($img, $to_x - 1, $to_y);
        
        if ($_c1 === $img_white || $_c2 === $img_white || $_c3 === $img_white) {
          imageline($img, $x1, $y1, $to_x, $to_y, $img_white);
          $x += $spacing + mt_rand(0, 5);
          continue 2;
        }
      }
      
      imageline($img, $x1, $y1, $to_x, $to_y, $img_white);
      $x += $spacing + mt_rand(0, 5);
    }
    
    imagesetthickness($img, 1);
    
    // Grain
    $this->addPowder($img_bg, $bg_width, $img_height, 0.1, 0);
    
    $this->addPowder($img, $img_width, $img_height, 0.15, 0);
    
    // Moon
    $_x_min = ceil($img_width * 0.3);
    $_x_max = ceil($img_width * 0.7);
    imagefilledellipse($img,
      mt_rand($_x_min, $_x_max),
      mt_rand(-20, -15),
      300, 50,
    $img_white);
    
    // Clear
    imagealphablending($img, false);
    imagefilledrectangle($img, 0, $clear_y, $img_width, $img_height, $img_clr_bg);
    imagealphablending($img, true);
    
    $this->flattenImageAlpha($img);
    $this->cleanImageAlpha($img_bg);
    
    if ($this->useInvert) {
      imagecolorset($img, 0, 128, 128, 128);
      imagecolorset($img_bg, 0, 215, 215, 215);
      imagecolorset($img, 2, 255, 255, 255);
      imagecolorset($img_bg, 1, 128, 128, 128);
    }
    else {
      imagecolorset($img, 0, 108, 108, 108);
      imagecolorset($img_bg, 0, 108, 108, 108);
    }
    
    return [ $challenge_str, $img, $img_width, $img_height, $img_bg, $bg_width, $fence_start ];
  }
  
  public function generateTwisterH($char_count) {
    if (!$this->font_id) {
      return false;
    }
    
    $challenge_str = $this->getRandomString($char_count);
    
    $background_str = $this->getRandomString($char_count + 2);
    
    list($img, $img_width, $img_height, $img_white) = $this->generateImage($challenge_str, $char_count);
    list($img_bg, $bg_width, $bg_height, $img_bg_white) = $this->generateBgImage($img_width + 49);
    
    if ($this->useEdgeBlock) {
      $this->addEdgeBlock($img, $img_width, $img_height);
    }
    
    if ($this->useInkBlot) {
      $this->addInkBlot($img, $img_width, $img_height);
    }
    
    if ($this->difficulty > self::LEVEL_EASY) {
      $this->addPowder($img, $img_width, $img_height, 0.08, 1);
    }
    
    if ($this->difficulty > self::LEVEL_EASY) {
      $this->addArc($img, $img_width, $img_height, false, 1);
    }
    
    $fence_start = $this->sliceVertical($img, $img_bg, $img_width, $img_height, $bg_width);
    
    if ($this->useScoreLines) {
      $this->addScoreLines($img, $img_width, $img_height, $clr_txt);
    }
    
    if ($this->useGridLines) {
      $this->addGridLines($img, $img_width, $img_height, $clr_txt);
    }
    
    if ($this->difficulty > self::LEVEL_EASY) {
      $this->addLines($img, $img_width, $img_height, $img_white);
    }
    
    $this->flattenImageAlpha($img);
    
    if ($this->difficulty > self::LEVEL_NORMAL) {
      $this->addPowder($img_bg, $bg_width, $img_height, 0.1, 1);
      $this->addPowder($img_bg, $bg_width, $img_height, 0.06, $img_bg_white);
      $this->addArc($img_bg, $bg_width, $img_height, false, $img_bg_white);
      $this->addArc($img_bg, $bg_width, $img_height, false, $img_bg_white);
    }
    else if ($this->difficulty > self::LEVEL_EASY) {
      $this->addPowder($img_bg, $bg_width, $img_height, 0.08, 1);
      $this->addPowder($img_bg, $bg_width, $img_height, 0.05, $img_bg_white);
      $this->addArc($img_bg, $bg_width, $img_height, false, $img_bg_white);
    }
    
    $this->cleanImageAlpha($img_bg);
    
    return [ $challenge_str, $img, $img_width, $img_height, $img_bg, $bg_width, $fence_start ];
  }
  
  private function generateFogBg() {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $img = imagecreatetruecolor($img_width, $img_height);
    $img_clr_bg = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $img_clr_bg);
    
    $c_width_min = ceil($img_height * 1.8);
    $c_width_max = ceil($img_height * 2.5);
    
    $x_step = ceil($img_width / 5);
    
    if ($x_step <= 0) {
      $x_step = 10;
    }
    
    $x_jitter = ceil($img_width * 0.05);
    
    $x = 0;
    
    $top = (bool)mt_rand(0, 1);
    
    while ($x <= $img_width) {
      if ($top) {
        $_y = 0;
      }
      else {
        $_y = $img_height;
      }
      
      $_w = mt_rand($c_width_min, $c_width_max);
      $_h = mt_rand($c_width_min, $c_width_max);
      
      $_x = $x + mt_rand(-$x_jitter, $x_jitter);
      
      $gray = imagecolorallocatealpha($img, 0, 0, 0, mt_rand(95, 100));
      
      imagefilledellipse($img, $_x, $_y, $_w, $_h, $gray);
      
      $x += $x_step;
      
      $top = !$top;
    }
    
    return $img;
  }
  
  private function generateSimplexBg($zoom_min = 62, $zoom_max = 68) {
    $noise = new SimplexNoise();
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $img = imagecreatetruecolor($img_width, $img_height);
    
    $color_count = 8;
    
    $color_base = 64;
    
    $color_range = round((255 - $color_base * 2) / 6);
    
    $colors = [];
    
    for ($i = 0; $i < $color_count; $i++) {
      $c = $color_base + $i * $color_range + mt_rand(-8, 8);
      $colors[] = imagecolorallocate($img, $c, $c, $c);
    }
    
    $zoom = mt_rand($zoom_min, $zoom_max);
    
    $_cc = $color_count - 1;
    
    for ($x = 0; $x < $img_width; ++$x) {
      for ($y = 0; $y < $img_height; ++$y) {
        $n = $noise->noise($x / $zoom, $y / $zoom);
        $c = floor((1 + $n) / 2 * $_cc);
        if ($c > $_cc) {
          $c = $_cc;
        }
        $ci = $colors[$c];
        imagesetpixel($img, $x, $y, $ci);
      }
    }
    
    return $img;
  }
  
  private function removeBackground($img) {
    $this->cleanImageAlpha($img);
    imagecolortransparent($img, 1);
  }
  
  private function overlayPattern($img, $type, $dark = true) {
    if ($type === 1) {
      $this->overlayPatternRings($img, $dark);
    }
    else if ($type === 2) {
      $this->overlayPatternRad($img, $dark);
    }
    else if ($type === 3) {
      $this->overlayPatternMoz($img, $dark);
    }
    else if ($type === 4) {
      $this->overlayPatternSimplexA($img, 16, 120);
    }
    else if ($type === 5) {
      $this->overlayPatternHatchA($img, 10, 2);
    }
    else if ($type === 6) {
      $this->overlayPatternSimplexA($img, 1, 110);
    }
  }
  
  private function overlayPatternHatchA($img, $noise_width = 10, $zoom = 2) {
    $noise = new SimplexNoise();
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $img_noise = imagecreatetruecolor($noise_width, $img_height);
    
    $c1 = imagecolorallocatealpha($img_noise, 0, 0, 0, 110);
    $c2 = imagecolorallocatealpha($img_noise, 255, 255, 255, 110);
    
    imagealphablending($img_noise, false);
    
    for ($x = 0; $x < $img_width; ++$x) {
      for ($y = 0; $y < $img_height; ++$y) {
        $n = $noise->noise($x / $zoom, $y / $zoom);
        $c = floor((1 + $n) / 2 * 255);
        if ($c < 128) {
          $c = $c1;
        }
        else {
          $c = $c2;
        }
        imagesetpixel($img_noise, $x, $y, $c);
      }
    }
    
    $img_noise = imagescale($img_noise, $img_width, $img_height);
    
    //$this->debugOutputImage($img_noise);
    
    imagealphablending($img, true);
    
    imagecopy($img, $img_noise, 0, 0, 0, 0, $img_width, $img_height);
  }
  
  private function overlayPatternSimplexA($img, $zoom = 16, $alpha = 120) {
    $noise = new SimplexNoise();
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    if ($alpha < 0) {
      $alpha = 0;
    }
    else if ($alpha > 127) {
      $alpha = 127;
    }
    
    $img_noise = imagecreatetruecolor($img_width, $img_height);
    
    $c1 = imagecolorallocatealpha($img_noise, 0, 0, 0, $alpha);
    $c2 = imagecolorallocatealpha($img_noise, 255, 255, 255, $alpha);
    
    imagealphablending($img_noise, false);
    
    if ($zoom <= 0) {
      $zoom = 1;
    }
    
    for ($x = 0; $x < $img_width; ++$x) {
      for ($y = 0; $y < $img_height; ++$y) {
        $n = $noise->noise($x / $zoom, $y / $zoom);
        $c = floor((1 + $n) / 2 * 255);
        if ($c < 128) {
          $c = $c1;
        }
        else {
          $c = $c2;
        }
        imagesetpixel($img_noise, $x, $y, $c);
      }
    }
    
    //$this->debugOutputImage($img_noise);
    
    imagealphablending($img, true);
    
    imagecopy($img, $img_noise, 0, 0, 0, 0, $img_width, $img_height);
  }
  
  private function overlayPatternRings($img, $dark = true) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    if ($dark) {
      $c_rings = imagecolorallocatealpha($img, 0, 0, 0, 110);
    }
    else {
      $c_rings = imagecolorallocatealpha($img, 255, 255, 255, 110);
    }
    
    imagealphablending($img, true);
    
    $ring_w = mt_rand(ceil($img_width * 0.06), ceil($img_width * 0.08));
    $ring_d = ceil($ring_w * 0.5) + 1;
    
    $x_max = $img_width + $ring_w;
    $y_max = $img_height + $ring_w;
    
    for ($x = mt_rand(1 - $ring_w, 0); $x < $x_max; $x += $ring_d) {
      for ($y = mt_rand(1 - $ring_w, 0); $y < $y_max; $y += $ring_d) {
        imageellipse($img, $x, $y, $ring_w, $ring_w, $c_rings);
      }
    }
  }
  
  private function overlayPatternRad($img, $dark = true) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    if ($dark) {
      $c_rings = imagecolorallocatealpha($img, 0, 0, 0, 110);
    }
    else {
      $c_rings = imagecolorallocatealpha($img, 255, 255, 255, 110);
    }
    
    imagealphablending($img, true);
    
    $x = mt_rand(round($img_width * 0.4), round($img_width * 0.6));
    $y = mt_rand(round($img_height * 0.4), round($img_height * 0.6));
    $w_d = 16;
    
    $w = $w_d;
    
    while ($w < $img_width) {
      imageellipse($img, $x, $y, $w, $w, $c_rings);
      imageellipse($img, $x, $y, $w + 2, $w + 2, $c_rings);
      $w += $w_d;
    }
  }
  
  private function overlayPatternMoz($img, $dark = true) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    imagealphablending($img, true);
    
    $color_map = [];
    
    $sq_w = 6;
    
    for ($x = 0; $x < $img_width; $x += $sq_w) {
      for ($y = 0; $y < $img_height; $y += $sq_w) {
        $_c = mt_rand(100, 115);
        
        if (isset($color_map[$_c])) {
          $c_rings = $color_map[$_c];
        }
        else {
          if ($dark) {
            $c_rings = imagecolorallocatealpha($img, 0, 0, 0, $_c);
          }
          else {
            $c_rings = imagecolorallocatealpha($img, 255, 255, 255, $_c);
          }
          
          $color_map[$_c] = $c_rings;
        }
        
        imagefilledrectangle($img, $x, $y, $x + $sq_w - 1, $y + $sq_w - 1, $c_rings);
      }
    }
  }
  
  private function applyTextToFogBg($img_chars, $img_bg) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $this->cleanImageAlpha($img_chars);
    
    imagealphablending($img_bg, true);
    
    $color_map = [];
    
    $c_text = imagecolorallocatealpha($img_bg, 0, 0, 0, mt_rand(70, 80));
    $c_text_alt = imagecolorallocatealpha($img_bg, 255, 255, 255, mt_rand(40, 50));
    
    if ($this->useInvert) {
      $_c = $c_text;
      $c_text = $c_text_alt;
      $c_text_alt = $_c;
    }
    
    $anti_x1 = mt_rand(round($img_width * 0.30), round($img_width * 0.45));
    $anti_x2 = mt_rand(round($img_width * 0.55), round($img_width * 0.60));
    
    $anti_x1_jitter = $anti_x1 - 2;
    $anti_x2_jitter = $anti_x2 + 2;
    
    for ($x = 0; $x < $img_width; ++$x) {
      for ($y = 0; $y < $img_height; ++$y) {
        $c_chars = imagecolorat($img_chars, $x, $y);
        
        // Character pixel, bright if inside the anti rect, dark otherwise
        if ($c_chars === 0) {
          $_anti = false;
          
          if ($this->useInkBlot) {
            if ($x >= $anti_x1 && $x <= $anti_x2) {
              $_anti = true;
            }
            else if ($x >= $anti_x1_jitter && $x < $anti_x1) {
              $_anti = !!mt_rand(0, 1);
            }
            else if ($x > $anti_x2 && $x <= $anti_x2_jitter) {
              $_anti = !!mt_rand(0, 1);
            }
          }
          
          if ($_anti) {
            imagesetpixel($img_bg, $x, $y, $c_text_alt);
          }
          else {
            imagesetpixel($img_bg, $x, $y, $c_text);
          }
        }
        
        $c_delta = mt_rand(100, 120);
        
        if (isset($color_map[$c_delta])) {
          $c_noise = $color_map[$c_delta];
        }
        else {
          $c_noise = imagecolorallocatealpha($img_bg, 0, 0, 0, $c_delta);
        }
        
        $color_map[$c_delta] = $c_noise;
        
        imagesetpixel($img_bg, $x, $y, $c_noise);
      }
    }
  }
    
  private function applyTextToSimplexBg($img_chars, $img_bg, $dirx = 2, $diry = 2) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    imagealphablending($img_bg, true);
    
    imagecolorset($img_chars, 0, 255, 255, 255, mt_rand(60, 70));
    imagecopy($img_bg, $img_chars, 0, 0, 0, 0, $img_width, $img_height);
    
    imagecolorset($img_chars, 0, 0, 0, 0, mt_rand(70, 80));
    imagecopy($img_bg, $img_chars, $dirx, $diry, 0, 0, $img_width, $img_height);
  }
  
  public function generateStatic($char_count) {
    if (!$this->font_id) {
      return false;
    }
    
    if ($this->useStaticRot) {
      $this->setFontScale(0.5);
    }
    
    $challenge_str = $this->getRandomString($char_count);
    
    list($img, $img_width, $img_height, $clr_bg) = $this->generateImage($challenge_str, $char_count);
    
    if (!$this->useTopBottomY) {
      $this->addNoise($img, $img_width, $img_height);
    }
    
    if ($this->useStaticRot) {
      $img = imagerotate($img, 15, $clr_bg);
      
      $_w = imagesx($img);
      $_h = imagesy($img);
      $_x = round(abs($_w - $img_width) * 0.5);
      $_y = round(abs($_h - $img_height) * 0.65);
      
      $img = imagecrop($img, ['x' => $_x, 'y' => $_y, 'width' => $img_width, 'height' => $img_height]);
    }
    else {
      // Overlay noise
      if ($this->difficulty > self::LEVEL_EASY) {
        list($img_char, $img_char_clr_txt, $img_char_clr_bg) = $this->prepareCharImage();
        
        $char_width_scaled = $this->font_width * 2.4;
        $char_height_scaled = $this->font_height * 2.4;
        
        $noise_count = ceil($char_count / 2);
        
        $dx = (int)($img_width / $noise_count);
        
        $x = mt_rand($char_width_scaled, $dx);
        
        for ($i = 0; $i < $noise_count; ++$i) {
          $this->drawChar($img, $img_char,
            self::CHAR_NOISE_OVERLAY[0],
            $x,
            (int)($img_height / 2) - (int)($char_height_scaled / 2),
            $char_width_scaled, $char_height_scaled,
            $img_char_clr_bg, $img_char_clr_txt
          );
          
          $x += mt_rand($char_width_scaled, $dx);
        }
      }
    }
    
    $this->cleanImageAlpha($img);
    
    if ($this->difficulty > self::LEVEL_EASY) {
      $this->addPowder($img, $img_width, $img_height, 0.08, 0);
      if (!$this->useStaticRot) {
        $this->addPowder($img, $img_width, $img_height, 0.06, 1);
      }
    }
    
    return [ $challenge_str, $img, $img_width, $img_height ];
  }
  
  private function applyNoiseToFog($img) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $img_noise = imagecreatetruecolor($img_width, $img_height);
    $img_noise_clr_bg = imagecolorallocate($img_noise, 255, 255, 255);
    $img_noise_clr_transp = imagecolorallocatealpha($img_noise, 0, 255, 0, 127);
    imagefill($img_noise, 0, 0, $img_noise_clr_bg);
    
    $this->addNoise($img_noise, $img_width, $img_height);
    
    $this->cleanImageAlpha($img_noise);
    
    imagecolortransparent($img_noise, 1);
    
    $_g = mt_rand(40, 80);
    imagecolorset($img_noise, 0, $_g, $_g, $_g);
    
    imagecopy($img, $img_noise, 0, 0, 0, 0, $img_width, $img_height);
  }
  
  public function generateSimpleTask($task_id = 0) {
    if (!$this->font_id) {
      return false;
    }
    
    $min_size = 24;
    $max_size = 32;
    
    $visual_count = 10;
    $visual_str = $this->getRandomString($visual_count);
    
    if ($task_id < 1 || $task_id > 2) {
      $task_id = mt_rand(1, 2);
    }
    
    $char_count = mt_rand(4, 6);
    
    $task_str_2 = '';
    
    // Type first or last characters
    if ($task_id == 1) {
      $_ary = [
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven'
      ];
      
      if (isset($_ary[$char_count]) && mt_rand(0, 1)) {
        $_char_count_str = $_ary[$char_count];
      }
      else {
        $_char_count_str = $char_count;
      }
      
      if (mt_rand(0, 1)) {
        $challenge_str = substr($visual_str, 0, $char_count);
        $task_str_1 = ('only type the first ' . $_char_count_str . ' characters');
      }
      else {
        $challenge_str = substr($visual_str, -$char_count);
        $task_str_1 = ('only type the last ' . $_char_count_str . ' characters');
      }
    }
    // Type characters with symbol above
    else if ($task_id == 2) {
      $task_str_1 = 'only type characters';
      $task_str_2 = ' with a circle above them';
      
      $picked_char_ids = array_rand(str_split($visual_str), $char_count);
      
      $challenge_str = '';
      
      foreach ($picked_char_ids as $key) {
        $challenge_str .= $visual_str[$key];
      }
    }
    
    // ---
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $img_half_height = round($img_height * 0.5);
    
    if ($this->useSimplexBg) {
      $img = $this->generateSimplexBg();
    }
    else {
      $img = $this->generateFogBg();
    }
    
    // ---
    
    $task_str_1_x = mt_rand(0, 20);
    
    if ($task_str_2) {
      $task_str_1_y = mt_rand(0, 2);
    }
    else {
      $task_str_1_y = mt_rand(0, 8);
    }
    
    imagestring($img, $this->font_id, $task_str_1_x, $task_str_1_y, $task_str_1, 0);
    
    if ($task_str_2) {
      $task_str_2_x = $task_str_1_x + mt_rand(20, 40);
      $task_str_2_y = $task_str_1_y + 12;
      imagestring($img, $this->font_id, $task_str_2_x, $task_str_2_y, $task_str_2, 0);
    }
    
    // ---
    
    $char_imgs = [];
    
    $x = 0;
    
    for ($i = 0; $i < $visual_count; $i++) {
      $_c = $visual_str[$i];

      list($char) = $this->getCharImage($_c, mt_rand($min_size, $max_size));
      
      if ($this->useSpecialRot) {
        $mat_rot = $this->getRotMatrix(-mt_rand(45, 65));
        $mat_shear_h = null;
        $mat_shear_v = null;
      }
      else {
        $mat_rot = $this->getRotMatrix(mt_rand(-25, 25));
        $mat_shear_h = $this->getShearHMatrix(mt_rand(-12, 12));
        $mat_shear_v = $this->getShearVMatrix(mt_rand(-25, 25));
      }
      
      $char = $this->transformImage($char, $mat_rot, $mat_shear_v, $mat_shear_h);
      
      $char_w = imagesx($char);
      $char_h = imagesy($char);
      
      $y = $img_half_height + 2;
      
      if ($this->useAltBlackWhite && mt_rand(0, 1)) {
        $c = 255;
        $alpha = mt_rand(55, 65);
      }
      else {
        $c = 0;
        $alpha = mt_rand(55, 75);
      }
      
      $char_imgs[] = [$char, $char_w, $char_h, $x, $y, $c, $alpha];
      
      $dx = mt_rand(-1, 2);
      
      $x += $char_w + $dx;
    }
    
    $x0 = max(0, $img_width - $x);
    $x0 = round($x0 * 0.5);
    
    foreach ($char_imgs as $_char) {
      list($char, $char_w, $char_h, $x, $y, $c, $alpha) = $_char;
      $this->mergeCharImage($img, $char, $x0 + $x, $y, $c, $alpha);
      
      if ($this->useEmboss) {
        $this->mergeCharImage($img, $char, $x0 + $x + 2, $y - 2, $c ? 0 : 255, $alpha);
      }
    }
    
    if ($this->useInvert) {
      imagefilter($img, IMG_FILTER_NEGATE);
    }
    
    $img_bg = $this->generateFogBg();
    
    $bg_chars = $this->generateSimplexBg();
    
    foreach ($char_imgs as $_char) {
      list($char, $char_w, $char_h, $x, $y, $c, $alpha) = $_char;
      $this->mergeCharImage($bg_chars, $char, $x0 + $x, $y, $c, $alpha);
      
      if ($this->useEmboss) {
        $this->mergeCharImage($bg_chars, $char, $x0 + $x + 2, $y - 2, $c ? 0 : 255, $alpha);
      }
    }
    
    imagestring($bg_chars, $this->font_id, $task_str_1_x, $task_str_1_y, $task_str_1, 0);
    
    if ($task_str_2) {
      imagestring($bg_chars, $this->font_id, $task_str_2_x, $task_str_2_y, $task_str_2, 0);
    }
    
    if ($this->useEdgeDetect && !$this->useEmboss) {
      $_b = 25;
      $_c = 20;
      
      imagefilter($img, IMG_FILTER_EDGEDETECT);
      imagefilter($bg_chars, IMG_FILTER_EDGEDETECT);
      
      imagefilter($img, IMG_FILTER_BRIGHTNESS, mt_rand(-$_b, $_b));
      imagefilter($img, IMG_FILTER_CONTRAST, mt_rand(-$_c, $_c));
      
      imagefilter($bg_chars, IMG_FILTER_BRIGHTNESS, mt_rand(-$_b, $_b));
      imagefilter($bg_chars, IMG_FILTER_CONTRAST, mt_rand(-$_c, $_c));
    }
    
    if ($this->useOverlayId) {
      $this->overlayPattern($img, $this->useOverlayId, $this->useOverlayDark);
    }
    
    if ($this->useGridLines) {
      $_c = imagecolorallocatealpha($img, 255, 255, 255, 105);
      $this->addGridLines($img, $img_width, $img_height, $_c, mt_rand(15, 20), 2);
    }
    
    $slider_range = $this->sliceVerticalSimplex($img, $img_bg, $bg_chars);
    
    // Print symbols for task 2
    if ($task_id == 2) {
      $_i = 0;
      
      foreach ($picked_char_ids as $key) {
        $_char = $char_imgs[$key];
        
        list($char, $char_w, $char_h, $x, $y, $c, $alpha) = $_char;
        
        $_dot_d = mt_rand(8, 10);
        
        if (mt_rand(0, 1)) {
          $_c = imagecolorallocatealpha($img, 255, 255, 255, 50);
        }
        else {
          $_c = imagecolorallocatealpha($img, 0, 0, 0, 50);
        }
        
        imagefilledellipse($img, $x0 + $x + round($char_w * 0.5) - 4, $y - mt_rand(7, 9), $_dot_d, $_dot_d, $_c);
        
        $_i++;
      }
    }
    
    //$this->debugOutputImage($char);
    
    imagetruecolortopalette($img, false, 50);
    
    return [ $challenge_str, $img, $img_width, $img_height, $img_bg, $img_width + $slider_range, 0 ];
  }
  
  public function generateTwisterHFogNew($char_count, $min_size = 38, $max_size = 52) {
    if (!$this->font_id) {
      return false;
    }
    
    if (!$min_size || $min_size < 0) {
      $min_size = 38;
    }
    
    if (!$max_size || $max_size < 0) {
      $max_size = 52;
    }
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $challenge_str = $this->getRandomString($char_count);
    
    if ($this->useSimplexBg) {
      $img = $this->generateSimplexBg();
    }
    else {
      $img = $this->generateFogBg();
    }
    
    $bleach = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(80, 100));
    $this->addPowder($img, $img_width, $img_height, 0.08, $bleach);
    
    $char_imgs = [];
    
    $x = 0;
    
    if ($this->useExtraSpaces) {
      $_extra_space_i = (int)($char_count * 0.5) + mt_rand(-1, 1);
    }
    
    for ($i = 0; $i < $char_count; $i++) {
      $_c = $challenge_str[$i];

      list($char) = $this->getCharImage($_c, mt_rand($min_size, $max_size));
      
      if ($this->useSpecialRot) {
        $mat_rot = $this->getRotMatrix(-mt_rand(45, 65));
        $mat_shear_h = null;
        $mat_shear_v = null;
      }
      else {
        $mat_rot = $this->getRotMatrix(mt_rand(-30, 30));
        $mat_shear_h = $this->getShearHMatrix(mt_rand(-15, 15));
        $mat_shear_v = $this->getShearVMatrix(mt_rand(-30, 30));
      }
      
      $char = $this->transformImage($char, $mat_rot, $mat_shear_v, $mat_shear_h);
      
      $char_w = imagesx($char);
      $char_h = imagesy($char);
      
      if ($img_height >= $char_h) {
        $y = mt_rand(0, $img_height - $char_h);
      }
      else {
        $y = $img_height - $char_h;
      }
      
      if ($this->useAltBlackWhite && mt_rand(0, 1)) {
        $c = 255;
        $alpha = mt_rand(55, 65);
      }
      else {
        $c = 0;
        $alpha = mt_rand(55, 75);
      }
      
      $char_imgs[] = [$char, $char_w, $char_h, $x, $y, $c, $alpha];
      
      $dx = mt_rand(-10, 2);
      
      $x += $char_w + $dx;
      
      if ($this->useExtraSpaces && $i == $_extra_space_i) {
        $x += $char_w + mt_rand(0, $char_w);
      }
    }
    
    $x0 = max(0, $img_width - $x);
    $x0 = round($x0 * 0.5);
    
    foreach ($char_imgs as $_char) {
      list($char, $char_w, $char_h, $x, $y, $c, $alpha) = $_char;
      $this->mergeCharImage($img, $char, $x0 + $x, $y, $c, $alpha);
      
      if ($this->useEmboss) {
        $this->mergeCharImage($img, $char, $x0 + $x + 2, $y - 2, $c ? 0 : 255, $alpha);
      }
    }
    
    if ($this->useInvert) {
      imagefilter($img, IMG_FILTER_NEGATE);
    }
    
    $img_bg = $this->generateFogBg();
    
    $bg_chars = $this->generateSimplexBg();
    
    foreach ($char_imgs as $_char) {
      list($char, $char_w, $char_h, $x, $y, $c, $alpha) = $_char;
      $this->mergeCharImage($bg_chars, $char, $x0 + $x, $y, $c, $alpha);
      
      if ($this->useEmboss) {
        $this->mergeCharImage($bg_chars, $char, $x0 + $x + 2, $y - 2, $c ? 0 : 255, $alpha);
      }
    }
    
    if ($this->useEdgeDetect && !$this->useEmboss) {
      $_b = 25;
      $_c = 20;
      
      imagefilter($img, IMG_FILTER_EDGEDETECT);
      imagefilter($bg_chars, IMG_FILTER_EDGEDETECT);
      
      imagefilter($img, IMG_FILTER_BRIGHTNESS, mt_rand(-$_b, $_b));
      imagefilter($img, IMG_FILTER_CONTRAST, mt_rand(-$_c, $_c));
      
      imagefilter($bg_chars, IMG_FILTER_BRIGHTNESS, mt_rand(-$_b, $_b));
      imagefilter($bg_chars, IMG_FILTER_CONTRAST, mt_rand(-$_c, $_c));
    }
    
    if ($this->useMeanRemoval && !$this->useEdgeDetect) {
      imagefilter($img, IMG_FILTER_MEAN_REMOVAL);
      imagefilter($bg_chars, IMG_FILTER_MEAN_REMOVAL);
    }
    
    if ($this->useOverlayId) {
      $this->overlayPattern($img, $this->useOverlayId, $this->useOverlayDark);
    }
    
    if ($this->useGridLines) {
      $_c = imagecolorallocatealpha($img, 255, 255, 255, 105);
      $this->addGridLines($img, $img_width, $img_height, $_c, mt_rand(15, 20), 2);
    }
    
    $slider_range = $this->sliceVerticalSimplex($img, $img_bg, $bg_chars);
    
    //$this->debugOutputImage($char);
    
    imagetruecolortopalette($img, false, 50);
    
    return [ $challenge_str, $img, $img_width, $img_height, $img_bg, $img_width + $slider_range, 0 ];
  }
  
  public function generateStaticFog($char_count) {
    if (!$this->font_id) {
      return false;
    }
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $challenge_str = $this->getRandomString($char_count);
    
    // Background image
    $img = $this->generateFogBg();
    
    // Text image
    list($img_chars) = $this->generateImage($challenge_str, $char_count);
    
    // Transparent line
    $this->addArc($img_chars, $img_width, $img_height, false, 1);
    
    // Copy the text to the bg
    $this->applyTextToFogBg($img_chars, $img);
    
    if ($this->useOverlayId) {
      $this->overlayPattern($img, $this->useOverlayId, $this->useOverlayDark);
    }
    
    if ($this->useScoreLines) {
      $this->addScoreLines($img, $img_width, $img_height, 1);
    }
    
    if ($this->useGridLines) {
      $this->addGridLines($img, $img_width, $img_height, 1);
    }
    
    // Top / Bottom symbol noise
    $this->applyNoiseToFog($img);
    
    // ---
    
    imagetruecolortopalette($img, false, 50);
    
    return [ $challenge_str, $img, self::IMG_WIDTH_MAX, self::IMG_HEIGHT ];
  }
  
  public function generateTwisterHFog($char_count) {
    if (!$this->font_id) {
      return false;
    }
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $challenge_str = $this->getRandomString($char_count);
    
    // Foreground fog
    $img = $this->generateFogBg();
    
    //$bleach = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(80, 100));
    //$grid_size = mt_rand(floor($img_height * 0.35), floor($img_height * 0.55));
    //$this->addGridLines($img, $img_width, $img_height, $bleach, $grid_size, 2);
    //$this->addPowder($img, $img_width, $img_height, 0.08, $bleach);
    
    // Background fog
    $img_bg = $this->generateFogBg();
    
    // Text image
    list($img_chars_orig) = $this->generateImage($challenge_str, $char_count);
    $img_chars = $this->cloneTextImage($img_chars_orig);
    
    $this->removeBackground($img_chars);
    
    // Copy the text to the main fog image
    $this->applyTextToFogBg($img_chars, $img);
    
    if ($this->useOverlayId) {
      $this->overlayPattern($img, $this->useOverlayId, $this->useOverlayDark);
    }
    
    // Slice
    $slider_range = $this->sliceVerticalFog($img, $img_bg, $img_chars_orig);
    
    // Perturbations for the character image
    $this->addArc($img_chars, $img_width, $img_height, false, 1);
    
    if ($this->useScoreLines) {
      $this->addScoreLines($img, $img_width, $img_height, 1);
    }
    
    if ($this->useGridLines) {
      $this->addGridLines($img, $img_width, $img_height, 1);
    }
    
    if (!$this->useTopBottomY) {
      $this->applyNoiseToFog($img);
    }
    
    // ---
    
    imagetruecolortopalette($img, false, 50);
    
    return [ $challenge_str, $img, $img_width, $img_height, $img_bg, $img_width + $slider_range, 0 ];
  }
  
  public function generateStaticSimplex($char_count) {
    if (!$this->font_id) {
      return false;
    }
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    $challenge_str = $this->getRandomString($char_count);
    
    // Background image
    $img = $this->generateSimplexBg();
    
    // Text image
    list($img_chars) = $this->generateImage($challenge_str, $char_count);
    
    $this->removeBackground($img_chars);
    
    $this->applyTextToSimplexBg($img_chars, $img, -2, 2);
    
    if ($this->useOverlayId) {
      $this->overlayPattern($img, $this->useOverlayId, $this->useOverlayDark);
    }
    
    if ($this->useInvert) {
      imagefilter($img, IMG_FILTER_NEGATE);
    }
    
    // ---
    
    imagetruecolortopalette($img, false, 50);
    
    return [ $challenge_str, $img, self::IMG_WIDTH_MAX, self::IMG_HEIGHT ];
  }
  
  public function generateTwisterHSimplex($char_count) {
    if (!$this->font_id) {
      return false;
    }
    
    $challenge_str = $this->getRandomString($char_count);
    
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    
    // Foreground fog
    $img = $this->generateSimplexBg();
    
    $bleach = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(80, 100));
    $grid_size = mt_rand(floor($img_height * 0.35), floor($img_height * 0.55));
    $this->addGridLines($img, $img_width, $img_height, $bleach, $grid_size, 2);
    $this->addPowder($img, $img_width, $img_height, 0.08, $bleach);
    
    // Background fog
    $img_bg = $this->generateFogBg();
    
    // Text image
    list($img_chars_orig) = $this->generateImage($challenge_str, $char_count);
    $img_chars = $this->cloneTextImage($img_chars_orig);
    
    $this->removeBackground($img_chars);
    
    // Copy the text to the main fog image
    $this->applyTextToSimplexBg($img_chars, $img, 2, -2);
    
    // Prepare the text for the bg image
    $bg_chars = imagecreatetruecolor($img_width, $img_height);
    $_c = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagealphablending($bg_chars, false);
    imagefilledrectangle($bg_chars, 0, 0, $img_width, $img_height, $_c);
    $this->applyTextToSimplexBg($img_chars, $bg_chars, 2, -2);
    
    if ($this->useOverlayId) {
      $this->overlayPattern($img, $this->useOverlayId, $this->useOverlayDark);
    }
    
    if ($this->useInvert) {
      imagefilter($img, IMG_FILTER_NEGATE);
    }
    
    if ($this->useScoreLines) {
      $this->addScoreLines($img, $img_width, $img_height, 1);
    }
    
    if ($this->useGridLines) {
      $this->addGridLines($img, $img_width, $img_height, 1);
    }
    
    // Slice
    $slider_range = $this->sliceVerticalSimplex($img, $img_bg, $bg_chars);
    
    // ---
    
    imagetruecolortopalette($img, false, 50);
    
    return [ $challenge_str, $img, $img_width, $img_height, $img_bg, $img_width + $slider_range, 0 ];
  }
  
  private function cloneTextImage($img) {
    $img_width = self::IMG_WIDTH_MAX;
    $img_height = self::IMG_HEIGHT;
    $tmp = imagecreatetruecolor($img_width, $img_height);
    imagealphablending($tmp, false);
    imagecopy($tmp, $img, 0, 0, 0, 0, $img_width, $img_height);
    imagealphablending($tmp, true);
    return $tmp;
  }
  
  public static function getChallengeHash($solution, $params) {
    $uniq_id = self::b64_encode_url(openssl_random_pseudo_bytes(self::CHALLENGE_ID_BYTES));
    
    if (!$uniq_id) {
      return false;
    }
    
    if (!is_array($params) || empty($params)) {
      return false;
    }
    
    $data = "$uniq_id $solution " . implode(' ', $params);
    
    $challenge_hash = hash_hmac('sha256', $data, base64_decode(self::$hmac_secret));
    
    if (!$challenge_hash) {
      return false;
    }
    
    return [ $uniq_id, $challenge_hash ];
  }
  
  public static function verifyChallengeHash($challenge_hash, $uniq_id, $solution, $params) {
    if (!$challenge_hash || !$uniq_id || !$solution) {
      return false;
    }
    
    if (!is_array($params) || empty($params)) {
      return false;
    }
    
    $data = "$uniq_id $solution " . implode(' ', $params);
    
    $this_hash = hash_hmac('sha256', $data, base64_decode(self::$hmac_secret));
    
    if (!$this_hash) {
      return false;
    }
    
    return $this_hash === $challenge_hash;
  }
  
  public static function getBase64Images($img, $img_bg = null) {
    ob_start();
    imagepng($img);
    $url_img = base64_encode(ob_get_contents());
    
    if ($img_bg) {
      ob_clean();
      imagepng($img_bg);
      $url_img_bg = base64_encode(ob_get_contents());
    }
    else {
      $url_img_bg = null;
    }
    
    ob_end_clean();
    
    return [ $url_img, $url_img_bg ];
  }
  
  public static function normalizeReponseStr($str) {
    return str_replace(
      ['F', 'B', 'O', 'Z', 'U', '5', '6', 'Q'],
      ['P', '8', '0', '2', 'V', 'S', 'G', '0'],
      strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', $str))
    );
  }
  
  private static function b64_encode_url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }
}

class SimplexNoise {
  const G2 = (3.0 - M_SQRT3) / 6.0;
  
  const Grad = [
    [1, 1],
    [-1, 1],
    [1, -1],
    [-1, -1],
    [1, 0],
    [-1, 0],
    [1, 0],
    [-1, 0],
    [0, 1],
    [0, -1],
    [0, 1],
    [0, -1],
  ];
  
  private $perm = [];
  private $permMod12 = [];
  
  public function __construct() {
    $max_rand = mt_getrandmax();
    
    $p = [];
    
    for ($i = 0; $i < 256; $i++) {
      $p[$i] = $i;
    }
    
    for ($i = 255; $i > 0; $i--) {
      $n = floor(($i + 1) * (mt_rand() / $max_rand));
      $q = $p[$i];
      $p[$i] = $p[$n];
      $p[$n] = $q;
    }
    
    for ($i = 0; $i < 512; $i++) {
      $this->perm[$i] = $p[$i & 255];
      $this->permMod12[$i] = $this->perm[$i] % 12;
    }
  }
  
  public function noise($x, $y) {
    $s = ($x + $y) * 0.5 * (M_SQRT3 - 1.0);
    $i = floor($x + $s);
    $j = floor($y + $s);
    $t = ($i + $j) * self::G2;
    $X0 = $i - $t;
    $Y0 = $j - $t;
    $x0 = $x - $X0;
    $y0 = $y - $Y0;

    $i1 = $x0 > $y0 ? 1 : 0;
    $j1 = $x0 > $y0 ? 0 : 1;

    $xG = $x0 + self::G2;
    $yG = $y0 + self::G2;
    $x1 = $xG - $i1;
    $y1 = $yG - $j1;
    $x2 = $xG + self::G2 - 1.0;
    $y2 = $yG + self::G2 - 1.0;
    
    $ii = $i & 255;
    $jj = $j & 255;
    $g0 = self::Grad[$this->permMod12[$ii + $this->perm[$jj]]];
    $g1 = self::Grad[$this->permMod12[$ii + $i1 + $this->perm[$jj + $j1]]];
    $g2 = self::Grad[$this->permMod12[$ii + 1 + $this->perm[$jj + 1]]];

    $t0 = 0.5 - $x0 * $x0 - $y0 * $y0;
    $n0 = $t0 < 0 ? 0.0 : ($t0 * $t0 * $t0 * $t0) * ($g0[0] * $x0 + $g0[1] * $y0);

    $t1 = 0.5 - $x1 * $x1 - $y1 * $y1;
    $n1 = $t1 < 0 ? 0.0 : ($t1 * $t1 * $t1 * $t1) * ($g1[0] * $x1 + $g1[1] * $y1);

    $t2 = 0.5 - $x2 * $x2 - $y2 * $y2;
    $n2 = $t2 < 0 ? 0.0 : ($t2 * $t2 * $t2 * $t2) * ($g2[0] * $x2 + $g2[1] * $y2);

    return 70.14805770653952 * ($n0 + $n1 + $n2);
  }
}
