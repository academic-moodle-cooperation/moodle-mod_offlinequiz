<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_offlinequiz;

/**
 * Tests for functions in locallib.php.
 *
 * @package    mod_offlinequiz
 * @category   test
 * @copyright  2026 Université de Montréal
 * @author     Salem Saidi <salem.saidi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('offlinequiz_apply_filters')]
final class locallib_test extends \advanced_testcase {
    /**
     * Load the library containing the function under test.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Returns a filter stub replacing every text by the given output.
     *
     * @param string $output
     * @return object
     */
    private function get_filter(string $output): object {
        return new class ($output) {
            /** @var string output returned by the filter */
            private $output;

            /**
             * Constructor.
             *
             * @param string $output
             */
            public function __construct(string $output) {
                $this->output = $output;
            }

            /**
             * Replaces the text.
             *
             * @param string $text
             * @return string
             */
            public function filter(string $text): string {
                return $this->output;
            }
        };
    }

    /**
     * PDF output must unwrap TeX images from the markup returned by Moodle's TeX filter.
     */
    public function test_apply_filters_removes_tex_link(): void {
        global $CFG;

        foreach (['displaytex', 'texdebug'] as $page) {
            // This markup is emitted by Moodle's filter_tex text filter.
            $tex = '<span id="preview" class="MathJax_Preview"><a id="formula" title="TeX" href="' . $CFG->wwwroot .
                '/filter/tex/' . $page . '.php?texexp=x"><img class="texrender rounded-0" title="x" alt="x" ' .
                'src="' . $CFG->wwwroot . '/filter/tex/pix.php/a.gif" align="middle" /></a></span>' .
                '<script type="math/tex">x</script>';
            $html = '<p>a ' . $tex . ' b ' . $tex . ' c</p>';

            $result = offlinequiz_apply_filters('ignored', [$this->get_filter($html)], true);

            $this->assertStringNotContainsString('<a ', $result);
            $this->assertStringNotContainsString('MathJax_Preview', $result);
            $this->assertSame(2, substr_count($result, '<img class="texrender rounded-0"'));
            $this->assertSame(2, substr_count($result, '<script type="math/tex">'));
        }
    }

    /**
     * The real Moodle TeX filter output must be converted to PDF-safe inline HTML.
     */
    public function test_apply_filters_with_real_tex_filter(): void {
        $this->resetAfterTest(true);

        $context = \context_system::instance();
        $filter = new \filter_tex\text_filter($context, []);
        $source = '<p>Before \(x^2\) after</p>';
        $filtered = $filter->filter($source);

        $this->assertStringContainsString('MathJax_Preview', $filtered);
        $this->assertStringContainsString('/filter/tex/', $filtered);
        $this->assertStringContainsString('<a ', $filtered);

        $result = offlinequiz_apply_filters($source, [$filter], true);

        $this->assertStringNotContainsString('MathJax_Preview', $result);
        $this->assertStringNotContainsString('<a ', $result);
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString(' after</p>', $result);
    }

    /**
     * Text without TeX formulas, including ordinary links, must stay unchanged.
     */
    public function test_apply_filters_keeps_other_content(): void {
        $html = '<p><b>Bold</b> <a href="http://example.com">link</a> <img src="http://example.com/a.png" /></p>';

        $this->assertSame($html, offlinequiz_apply_filters($html, [$this->get_filter($html)]));
    }

    /**
     * TeX links must remain intact for preview and DOCX output.
     */
    public function test_apply_filters_keeps_tex_links_for_non_pdf(): void {
        global $CFG;

        $html = '<span class="MathJax_Preview"><a href="' . $CFG->wwwroot . '/filter/tex/displaytex.php?texexp=x">' .
            '<img src="' . $CFG->wwwroot . '/filter/tex/pix.php/a.gif" /></a></span>' .
            '<script type="math/tex">x</script>';

        $this->assertSame($html, offlinequiz_apply_filters($html, [$this->get_filter($html)]));
    }

    /**
     * Do not unwrap a link that does not point to Moodle's TeX filter.
     */
    public function test_apply_filters_keeps_non_tex_links_in_pdf(): void {
        $html = '<span class="MathJax_Preview"><a href="https://example.com/formula">' .
            '<img src="https://example.com/formula.png" /></a></span>';

        $this->assertSame($html, offlinequiz_apply_filters($html, [$this->get_filter($html)], true));
    }
}
