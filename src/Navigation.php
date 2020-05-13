<?php

namespace Spatie\Navigation;

use RuntimeException;
use Spatie\Navigation\Helpers\ActiveUrlChecker;
use Spatie\Navigation\Renderers\BreadcrumbsRenderer;
use Spatie\Navigation\Renderers\TreeRenderer;

class Navigation
{
    use HasChildSections;

    private ActiveUrlChecker $activeUrlChecker;

    public function __construct(ActiveUrlChecker $activeUrlChecker)
    {
        $this->activeUrlChecker = $activeUrlChecker;

        $this->children = [];
    }

    public function isActive(Section $section): bool
    {
        return $this->activeUrlChecker->check($section->url);
    }

    public function activeSection(): Section
    {
        return collect($this->filter([$this, 'isActive']))
            ->sortByDesc(function (Section $section) {
                return count(explode('/', preg_replace('/^https?:\/\//', '', $section->url)));
            })
            ->first(null, function () {
                throw new RuntimeException("No active section was found");
            });
    }

    public function filter(callable $callback): array
    {
        return $this->filterSections($this->children, $callback);
    }

    private function filterSections(array $sections, callable $callback): array
    {
        $filtered = [];

        foreach ($sections as $section) {
            if ($callback($section)) {
                $filtered[] = $section;
            }

            foreach ($this->filterSections($section->children, $callback) as $section) {
                $filtered[] = $section;
            }
        }

        return $filtered;
    }

    public function tree(): array
    {
        return (new TreeRenderer($this))->render();
    }

    public function breadcrumbs(): array
    {
        return (new BreadcrumbsRenderer($this))->render();
    }
}