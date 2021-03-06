<?php
/**
 * @package   	JCE
 * @copyright 	Copyright (c) 2009-2012 Ryan Demmer. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('_JEXEC') or die('RESTRICTED');

$count = 0;

foreach ($this->plugins as $plugin) :

    if ($plugin->type == 'plugin') :
        $path = JPATH_SITE . $plugin->path;
        $manifest = $path . '/' . $plugin->name . '.xml';

        if ($plugin->editable && is_file($manifest)) :

            jimport('joomla.filesystem.folder');
            jimport('joomla.filesystem.file');

            $name = trim($plugin->name);
            $params = new WFParameter($this->profile->params, $manifest, $plugin->name);

            // set element paths
            $params->addElementPath(array(
                WF_EDITOR . '/elements'
            ));

            // set plugin specific elements
            if (JFolder::exists($path . '/elements')) {
                $params->addElementPath($path . '/elements');
            }

            $class = in_array($plugin->name, explode(',', $this->profile->plugins)) ? 'tabs-plugin-parameters' : 'tabs-plugin-parameters ui-tabs-hidden';
            $groups = $params->getGroups();

            if (count($groups)) :
                $count++;
                ?>
                <div id="tabs-plugin-<?php echo $plugin->name; ?>" data-name="<?php echo $plugin->name; ?>" class="<?php echo $class; ?>">
                    <h2><?php echo WFText::_($plugin->title); ?></h2>
                    <?php
                    // Draw parameters
                    foreach ($groups as $group) :
                        echo '<fieldset class="adminform panelform"><legend>' . WFText::_('WF_PROFILES_PLUGINS_' . strtoupper($group)) . '</legend>';
                        echo '<p>' . WFText::_('WF_PROFILES_PLUGINS_' . strtoupper($group) . '_DESC') . '</p>';
                        //echo $params->render('params[' . $plugin->name . '][' . $group . ']', $group);
                        echo $params->render('params[' . $plugin->name . ']', $group);
                        echo '</fieldset>';
                    endforeach;

                    $extensions = $this->model->getExtensions($plugin->name);

                    // Get extensions supported by this plugin
                    foreach ($extensions as $type => $items) :
                        $html = '';

                        // get extension type specific parameters
                        $file = WF_EDITOR_LIBRARIES . '/xml/config/' . $type . '.xml';

                        if (is_file($file)) {
                            $params = new WFParameter($this->profile->params, $file, $plugin->name . '.' . $type);

                            // add element paths
                            $params->addElementPath(array(
                                WF_EDITOR . '/elements'
                            ));

                            foreach ($params->getGroups() as $group) :
                                $html .= $params->render('params[' . $plugin->name . '][' . $type . ']', $group);
                            endforeach;
                        }

                        foreach ($items as $extension) :
                            // get extension xml file
                            $file = $extension->manifest;
                            if ($extension->core == 0) {
                                // Load extension language file
                                $language = JFactory::getLanguage();
                                $language->load('com_jce_' . $extension->folder . '_' . trim($extension->extension), JPATH_SITE);
                            }

                            if (JFile::exists($file)) :
                                // get params for plugin
                                $key = $plugin->name . '.' . $type . '.' . $extension->extension;
                                $params = new WFParameter($this->profile->params, $file, $key);

                                // add element paths
                                $params->addElementPath(array(
                                    WF_EDITOR . '/elements'
                                ));

                                // render params
                                if (!$params->hasParent()) :
                                    $key = array($plugin->name, $type, $extension->extension);

                                    $enabled = (int) $params->get('enable', 1);
                                    $checked = $enabled ? ' checked="checked"' : '';

                                    $html .= '<h3><input type="hidden" id="params' . implode('', $key) . 'enable" name="params[' . implode('][', $key) . '][enable]" value="' . $enabled . '" /><input type="checkbox" data-name="' . $extension->extension . '" class="plugins-enable-checkbox"' . $checked . '/>' . WFText::_($extension->name) . '</h3>';
                                    $html .= '<p>' . WFText::_($extension->description) . '</p>';
                                    foreach ($params->getGroups() as $group) :
                                        $html .= $params->render('params[' . implode('][', $key) . ']', $group, array('enable'));
                                    endforeach;
                                endif;
                            endif;
                        endforeach;

                        if ($html) :
                            echo '<fieldset class="adminform panelform"><legend>' . WFText::_('WF_EXTENSIONS_' . strtoupper($type) . '_TITLE') . '</legend>';
                            echo $html;
                            echo '</fieldset>';
                        endif;
                    endforeach;
                    ?>
                </div>
                <?php
            endif;
        endif;
    endif;
endforeach;

if (!$count) {
    echo WFText::_('WF_PROFILES_NO_PLUGINS');
}
?>