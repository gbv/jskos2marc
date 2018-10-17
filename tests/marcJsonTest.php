<?php declare(strict_types = 1);

use function JSKOS\{jskos2marc, jskos2marcjson, marcxml, jskos_decode};

class MarcJsonTest extends \PHPUnit\Framework\TestCase
{
    public function testExample() {
        $jskos = jskos_decode(file_get_contents('examples/minimal.json'));
        $marc = [
            [
                ['LDR', '', '', '', "00000nz  a2200000nc 4500"],
                ['100', '1', ' ', 'a', 'example']
            ] 
        ];

        $this->assertEquals(jskos2marc($jskos), $marc);

        $marcjson = json_encode($marc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->assertEquals(jskos2marcjson($jskos), $marcjson);

        $expect = file_get_contents('examples/minimal.xml');
        $marcxml = implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            marcxml($marc[0], 'Authority', 'http://www.loc.gov/MARC21/slim')
        ]);
        $this->assertEquals($marcxml, $expect);
    }    

}

