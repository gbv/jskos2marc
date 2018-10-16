<?php declare(strict_types = 1);

use function JSKOS\{jskos2marcxml, jskos_decode};

class ExamplesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider examplesProvider
     */
    public function testExample($jskosfile, $xmlfile) {
        $jskos = jskos_decode(file_get_contents($jskosfile));
        $xml = file_get_contents($xmlfile);
        $this->assertEquals(jskos2marcxml($jskos), $xml);
    }    

    public function examplesProvider() {
        $examples = [];
        $files = glob ('tests/examples/*.json');
        foreach ($files as $jskosfile) {        
            $xmlfile = preg_replace('/\.json$/', '.xml', $jskosfile);
            if (file_exists($xmlfile)) {
                $examples[] = [$jskosfile, $xmlfile];
            }
        }
        return $examples;
    }
}
