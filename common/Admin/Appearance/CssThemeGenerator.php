<?php namespace Common\Admin\Appearance;

use File;
use Leafo\ScssPhp\Block;
use Leafo\ScssPhp\Parser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Common\Settings\Settings;

class CssThemeGenerator
{
    /**
     * @var Settings
     */
    private $settings;

    private $variables;

    private $matches = [];

    private $cache = [];

    /**
     * CssThemeGenerator constructor.
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        $this->variables = config('common.appearance.variables');
    }

    public function generate()
    {
        $dirs = [
            base_path('../client/src/app'),
            base_path('../client/src/scss'),
            base_path('../client/src/common')
        ];

        $parser = new Parser(null);

        $files = iterator_to_array(Finder::create()->name('*.scss')->files()->in($dirs), false);

        foreach ($files as $file) {
            $tree = $parser->parse(file_get_contents($file->getRealPath()));
            $this->parseSassBlock($tree);
        }

        $this->generateCss();
    }

    /**
     * Generate a valid css file from sass files of components that contain variables.
     *
     * @return string
     */
    private function generateCss()
    {
        $grouped = $this->groupMatchesByVariable();
        $css = '';

        foreach ($grouped as $variable => $group) {
            foreach ($group as $property => $matches) {
                //remove "::webkit" stuff as it breaks css in other browsers
                $selectors = array_filter($matches, function($match) {
                    return ! str_contains($match['selector'], '::-webkit');
                });

                //prepend '#theme' to all selectors
                $selectors = array_map(function($match) {
                    return $this->prependThemePrefixToSelector($match['selector']);
                }, $selectors);

                $selectors = join($selectors, ",\n");

                $css .= "$selectors\n{\n\t$property: var(--$variable);\n}\n\n";
            }
        }

        //TEMP: TODO webkit scrollbars
        $css .= $this->getWebkitScrollbarsCss();

        $rootBlock = $this->generateCssVariablesRootBlock();

        $css = $rootBlock . $css;

        File::put(resource_path('editable-theme.css'), $css);

        return $css;
    }

    /**
     * Generate css :root block containing variables.
     *
     * @return string
     */
    private function generateCssVariablesRootBlock()
    {
        $sassVars = $this->getSassVariableFileContents();

        $variables = collect($this->variables)->mapWithKeys(function($variable) use($sassVars) {
            return [$variable => $this->extractVariableValue($sassVars, $variable)];
        })->map(function($value, $name) {
            return "\t--$name: $value;";
        })->implode("\n");

        return ":root {\n$variables\n}\n\n";
    }

    /**
     * Extract specified sass variable value recursively.
     *
     * @param string $sass
     * @param string $variable
     * @param bool $recursive
     * @return string
     */
    private function extractVariableValue($sass, $variable, $recursive = true)
    {
        //extract value from sass map
        if (str_contains($variable, 'map_get')) {
            $materialSass = file_get_contents(base_path('../client/node_modules/@angular/material/_theming.scss'));
            preg_match("/map_get\((.+?),.([0-9]+)\)/", $variable, $matches);

            $mapName = '\\'.$matches[1];
            $varName = $matches[2];

            preg_match("/$mapName:.\(.+?$varName:.(#[a-z0-9]+),/s", $materialSass, $matches);

            return $matches[1];
        }

        if (str_contains($variable, '$')) {
            $variable = str_replace('$', '\$', $variable);
            $variable = '\$'.$variable;
        }
        preg_match("/$variable:(.+?);/", $sass, $matches);

        try {
            $value = trim(str_replace('!default', '', $matches[1]));
        } catch (\Exception $e) {
            $value = $variable;
        }

        if (str_contains($value, '$') && $recursive) {
            $value = $this->extractVariableValue($sass, $value);
        }

        return $value;
    }

    /**
     * Get contents of all _variables.scss files.
     *
     * @return string
     */
    private function getSassVariableFileContents()
    {
        if (isset($this->cache['variableFileContents'])) return $this->cache['variableFileContents'];

        $dirs = array(base_path('../client/src/common'), base_path('../client/src/'));

        $files = iterator_to_array(Finder::create()->name('*_variables.scss')->files()->in($dirs), false);


        // reverse array so app specific variables are always before framework variables
        $contents = collect($files)->map(function(SplFileInfo $file) {
            return File::get($file->getRealPath());
        })->unique()->reverse()->implode('');

        $this->cache['variableFileContents'] = $contents;

        return $this->cache['variableFileContents'];
    }

    /**
     * Group all matches by variable name and then by css property name.
     *
     * @return array
     */
    private function groupMatchesByVariable() {
        $grouped = [];

        //group matches by variable, example "site-bg-color-400"
        foreach ($this->matches as $match) {
            $grouped[$match['variable']][] = $match;
        }

        //group each variable group by property name, example: "border-color"
        foreach ($grouped as $groupName => $group) {
            foreach ($group as $matchKey => $match) {
                $grouped[$groupName][$match['property']][] = $match;
                unset($grouped[$groupName][$matchKey]);
            }
        }

        return $this->addMaterialSelectorsToGroupedVariables($grouped);
    }

    /**
     * @param Block $block
     */
    private function parseSassBlock(Block $block)
    {
        foreach ($block->children as $child) {
            $childType = $child[0];
            $childBlock = $child[1];

            if ($childType === 'block') {
                if ($block->selectors) $childBlock->parent = $block;
                $this->getSelectorsFromBlock($childBlock);
                $this->parseSassBlock($childBlock);
            }
        }
    }

    /**
     * Extract css selectors that contain variables from specified sass block.
     *
     * @param Block $block
     */
    private function getSelectorsFromBlock(Block $block) {
        foreach ($block->children as $child) {
            $childType = $child[0];

            if ($childType === 'assign') {
                $assignKey = $child[1][2][0];
                $assignValueType = $child[2][0];
                $variableName = $this->getVariableNameFromSassValue($child);

                $variableIndex = array_search($variableName, $this->variables);

                if ($variableIndex === false) continue;

                if ($assignValueType === 'list' && str_contains($assignKey, 'border')) {
                    $this->addMatch($block, 'border-color', $variableIndex);
                }

                else if ($assignValueType === 'var' && $variableIndex > -1) {
                    $this->addMatch($block, $assignKey, $variableIndex);
                } else if ($assignValueType === 'fncall') {
                    // TODO: parse and use function properly instead
                    // of just overriding it with a static variable
                    //$this->addMatch($block, $assignKey, $variableIndex);
                }
            }
        }
    }

    /**
     * Extract variable name from block child.
     *
     * @param array $blockChild
     * @return string|null;
     */
    private function getVariableNameFromSassValue($blockChild)
    {
        if ( ! is_array($blockChild[2])) return null;

        $flattened = array_flatten($blockChild[2]);

        foreach ($flattened as $key => $value) {
            if ($value === 'var') {
                return $flattened[$key+1];
            }
        }

        return null;
    }

    /**
     * @param Block $block
     * @param $assignKey
     * @param $variableIndex
     */
    private function addMatch(Block $block, $assignKey, $variableIndex)
    {
        $parents = $this->makeParentSelector($block);
        $child  = $this->makeSelectorString($block->selectors);
        $children = explode(',', $child);

        $final = [];

        // merge all parent and child selectors
        // [parent1, parent2], [child1, child2] => [parent1 child1, parent1 child2, parent2 child1, parent2 child2]
        foreach ($parents as $parent) {
            foreach ($children as $child) {
                // convert sass '&' symbols into css, by removing them
                // selector&.child => selector.child
                if (str_contains($child, '&') || str_contains($parent, '&')) {
                    $final[] = str_replace([' &', '& ', '&'], '', $parent.$child);
                } else {
                    $final[] = $parent ? $parent.' '.$child : $child;
                }
            }
        }

        $selector = implode(', ', $final);
        $selector = preg_replace('!\s+!', ' ', $selector);

        //.selector>.child => .selector > .child
        $selector = preg_replace("/([a-z])>/", "$1 >", $selector);

        $this->matches[] = [
            'selector' => $selector,
            'property' => $assignKey,
            'variable' => $this->variables[$variableIndex],
        ];
    }

    /**
     * Generate selectors string from specified sass selectors array.
     *
     * @param array $selectors
     * @return string
     */
    private function makeSelectorString($selectors)
    {
        $string = '';

        foreach ($selectors as $key => $selector) {
            foreach ($selector as $selectorParts) {
                $self = isset($selectorParts[0][0]) && $selectorParts[0][0] === 'self';
                //start space between selectors
                if (! $self) $string .= ' ';

                foreach ($selectorParts as $innerPart) {
                    if (is_string($innerPart)) {
                        $string .= $innerPart;

                        if ($innerPart === '>') {
                            $string .= ' ';
                        }
                    } else if ($innerPart[0] === 'self') {
                        $string .= '&';
                    } else if ($innerPart[0] === 'string' && isset($innerPart[2])) {
                        $string .= $innerPart[2][0];
                    }
                }
            }

            if (isset($selectors[$key + 1])) {
                $string .= ', ';
            }
        }

        return $string;
    }

    /**
     * Generate parent selector to the root parent for specified block.
     *
     * @param Block $block
     * @return array
     */
    private function makeParentSelector(Block $block)
    {
        $parent = isset($block->parent) ? $block->parent : null;
        $parentSelector = [];

        while ($parent) {
            $parentSelector[] = $this->makeSelectorString($parent->selectors);
            $parent = isset($parent->parent) ? $parent->parent : null;
        }

        //order array from parent to child
        $parentSelector = array_reverse($parentSelector);

        $selectors = [''];

        foreach ($parentSelector as $selectorPart) {
            //multiple selectors, will need to prepend previous parents to all of them
            if (str_contains($selectorPart, ',')) {
                $parts = explode(',', $selectorPart);

                $selectors = array_map(function($selector) use($selectors) {
                    return implode(' ', $selectors) . trim($selector);
                }, $parts);
            } else {
                foreach ($selectors as $key => $selector) {
                    $selectors[$key] = $selectors[$key] . ' ' . $selectorPart . ' ';
                }
            }
        }

        $selectors = array_map(function($selector) {
            return trim(str_replace('  ', ' ', $selector));
        }, $selectors);

        return $selectors;
    }

    /**
     * Prepend "#theme" prefix to all specified selector parts.
     *
     * @param string $selector
     * @return string
     */
    private function prependThemePrefixToSelector($selector)
    {
        $parts = explode(',', $selector);

        $parts = array_map(function($selectorPart) {
            $trimmed = trim($selectorPart);
            //remove > so, selectors are never direct child of #theme
            $normalized = preg_replace("/^> /", '', $trimmed);
            return '#theme '.trim($normalized);
        }, $parts);

        return implode(', ', $parts);
    }

    /**
     * Add angular material specific accent selectors to the theme.
     *
     * @param array $grouped
     * @return array
     */
    private function addMaterialSelectorsToGroupedVariables($grouped)
    {
        $grouped['site-accent-color']['background-color'][] = [
            'selector' => '.mat-raised-button.mat-accent:not([disabled]), .mat-fab.mat-accent, .mat-mini-fab.mat-accent',
        ];

        $grouped['site-accent-color']['background-color'][] = [
            'selector' => '.mat-flat-button.mat-accent:not([disabled]), .mat-fab.mat-accent, .mat-mini-fab.mat-accent',
        ];

        $grouped['site-accent-color']['background-color'][] = [
            'selector' => '.mat-checkbox-checked.mat-accent .mat-checkbox-background, .mat-checkbox-indeterminate.mat-accent .mat-checkbox-background',
        ];

        $grouped['site-accent-color']['color'][] = [
            'selector' => ' .mat-button.mat-accent, .mat-icon-button.mat-accent'
        ];

        $grouped['site-primary-color-100']['background-color'][] = [
            'selector' => ' .mat-progress-bar-buffer'
        ];

        $grouped['site-primary-color-200']['background-color'][] = [
            'selector' => ' .mat-raised-button.mat-primary, .mat-icon-button.mat-primary, .mat-progress-bar-fill::after',
        ];

        $grouped['site-primary-color-200']['background-color'][] = [
            'selector' => '.mat-checkbox-checked.mat-primary .mat-checkbox-background, .mat-checkbox-indeterminate.mat-primary .mat-checkbox-background',
        ];

        $grouped['site-primary-color-200']['background-color'][] = [
            'selector' => '.mat-radio-button.mat-primary .mat-radio-inner-circle',
        ];

        $grouped['site-primary-color-200']['border-color'][] = [
            'selector' => '.mat-radio-button.mat-primary.mat-radio-checked .mat-radio-outer-circle',
        ];

        $grouped['site-primary-color-200']['border-color'][] = [
            'selector' => '.mat-tab-group.mat-primary .mat-ink-bar, .mat-tab-nav-bar.mat-primary .mat-ink-bar',
        ];

        $grouped['site-primary-color-200']['background-color'][] = [
            'selector' => '.mat-step-header .mat-step-icon',
        ];

        $grouped['site-primary-color-200']['color'][] = [
            'selector' => '.la-ball-spin-clockwise',
        ];

        $grouped['site-bg-color-100']['background-color'][] = [
            'selector' => ' .mat-menu-panel'
        ];

        $grouped['site-text-color-400']['color'][] = [
            'selector' => ' .mat-menu-item'
        ];

        // TODO: TEMP FOR BEMUSIC
        if (array_search('wp-bg-color-500', $this->variables) > -1) {
            $grouped['wp-bg-color-600']['background-color'][] = [
                'selector' => '.auth-page .auth-panel, account-settings .panel',
            ];

            $grouped['wp-bg-color-500']['background'][] = [
                'selector' => '.auth-page, account-settings, .fullscreen-overlay.maximized',
            ];

            $grouped['wp-bg-color-400']['background-color'][] = [
                'selector' => '.auth-page .auth-panel, .auth-page > .auth-panel .input-container > input, account-settings input, account-settings select, .web-player-theme .mat-dialog-container',
            ];

            $grouped['wp-border-color-200']['border-color'][] = [
                'selector' => 'account-settings .panel, account-settings input, account-settings select',
            ];

            $grouped['wp-bg-color-300']['background-color'][] = [
                'selector' => '.auth-page > .auth-panel .input-container > input',
            ];

            $grouped['wp-text-color-200']['color'][] = [
                'selector' => '.auth-page',
            ];

            $grouped['site-accent-color']['color'][] = [
                'selector' => '.auth-page a',
            ];
        }

        return $grouped;
    }

    private function getWebkitScrollbarsCss() {
        $selector = 'web-player ::-webkit-scrollbar-thumb, .web-player-theme ::-webkit-scrollbar-thumb';
        $css = "$selector\n{\n\tbackground: var(--wp-bg-color-300);\n}\n\n";
        $selector2 = 'web-player ::-webkit-scrollbar-thumb:hover, .web-player-theme ::-webkit-scrollbar-thumb:hover';
        $css.="\n$selector2\n{\n\tbackground: var(--wp-bg-color-200);\n}\n\n";
        return $css;
    }
}