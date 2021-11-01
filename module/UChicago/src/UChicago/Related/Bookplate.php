<?php

namespace UChicago\Related;

class Bookplate extends \VuFind\Related\Bookplate
{
    /**
     * Get bookplate details for display.
     *
     * @return array
     */
    public function getBookplateDetails()
    {
        $hasBookplates = !empty($this->bookplateStrs);
        if ($hasBookplates) {
            $data = [];
            foreach ($this->bookplateStrs as $i => $bookplate) {
                $tokens = [
                    '%%img%%',
                    '%%thumb%%',
                ];
                $tokenValues = [
                    $this->bookplateImages[$i] ?? '',
                    $this->bookplateThumbnails[$i] ?? '',
                ];
                $imgUrl = str_replace(
                    $tokens,
                    $tokenValues,
                    $this->fullUrlTemplate
                );
                $imgThumb = str_replace(
                    $tokens,
                    $tokenValues,
                    $this->thumbUrlTemplate
                );
                $data[$i] = ['title' => $bookplate,
                             'fullUrl' => strtolower($imgUrl),
                             'thumbUrl' => strtolower($imgThumb),
                             'displayTitle' => $this->displayTitles];
            }
            return $data;
        }
        return [];
    }

}

