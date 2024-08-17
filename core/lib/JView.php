<?php
namespace mt\core\lib;

/**
 * 模板页面渲染
 */
class JView
{
    public static function render($view, $params=[], $layout='main')
    {
        $config = JConfig::getEnv('view');
        $_file = appBasePath() .'/theme/'
            . ($config['theme']??'basic')
            .'/'.$view
            .($config['suffix']??'.phtml');
        $content = static::renderPhpFile($_file, $params);
        if (empty($layout)) {
            return $content;
        }

        $_layout = appBasePath() .'/theme/'
            . ($config['theme']??'basic')
            .'/layout-'. $layout
            .($config['suffix']??'.phtml');
        return static::renderPhpFile($_layout, [
            'content' => $content,
        ]);
    }

    public static function renderPhpFile($_file_, $_params_ = [])
    {
        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        try {
            require $_file_;
            return ob_get_clean();
        } catch (\Exception $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }


}