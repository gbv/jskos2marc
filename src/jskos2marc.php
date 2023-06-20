<?php

namespace JSKOS;

// 1. JSKOS too MARC JSON :

// https://github.com/scriptotek/mc2skos#mapping-schema-for-marc21-authority

function jskos2marc(array $jskosRecords, array $options=[]) {
  // TODO: take default language from http://www.loc.gov/marc/authority/ad040.html

  $primaryLanguage = $options['language'] ?? 'en';

  $languages = getParameter_Languages($_GET);
  if($languages) {
    $primaryLanguage = key($languages);
  }

  $marcRecords = [];

  foreach($jskosRecords as $jskos) {
      $marc = [];

      // LEADER
      $marc[] = [
          'LDR', null, null, '_', '00000nz  a2200000nc 4500'
      ];

      // modified
      if (isset($jskos['modified'])) {
          $modifiedDate = $jskos['modified'];
          $modifiedDate = strtotime($modifiedDate);
          $modifiedDate = date('YmdHis.0', $modifiedDate);
          $marc[] = [
              '005', null, null, '_', $modifiedDate
          ];
      }

      // created
      if (isset($jskos['created'])) {
          $modifiedDate = $jskos['created'];
          $modifiedDate = strtotime($modifiedDate);
          $modifiedDate = date('YmdHis.0', $modifiedDate);
          $marc[] = [
              '008', null, null, '_', $modifiedDate
          ];
      }

      // uri
      if (isset($jskos['uri'])) {
          $marc[] = [
              '024', '7', ' ', 'a', $jskos['uri'],
              '2', 'uri'
          ];
      }

      // identifier
      foreach ( $jskos['identifier'] ?? [] as $identifier) {
          $marc[] = [
            '035', ' ', ' ', 'a', $identifier
          ];
      }

      // notation
      foreach ($jskos['notation'] ?? [] as $notation) {
          $marc[] = [
            '035', ' ', ' ', 'a', $notation
          ];
      }

      //vocname = Klassifikationskennzeichnung
      if (isset($jskos['inScheme'])) {
          if(isset($jskos['inScheme'][0]['notation'])) {
            foreach($jskos['inScheme'][0]['notation'] as $notation) {
              $marc[] = [
                '084', '0', ' ', 'a', $notation
              ];
            }
          }
      }

      // record-type
      // copy from gnd (dnb) https://www.dnb.de/gndgeneraltype
      /*
      b Körperschaft / kiz
      g Geografikum / giz
      p Person (individualisiert) / piz
      s Sachbegriff / concept / guideterm / siz

      <datafield tag="075" ind1=" " ind2=" ">
        <subfield code="b">p</subfield>
        <subfield code="2">gndgen</subfield>
      </datafield>
      <datafield tag="075" ind1=" " ind2=" ">
        <subfield code="b">piz</subfield>
        <subfield code="2">gndspec</subfield>
      </datafield>
      */
      if(isset($jskos['type'])) {
        $foundType = false;
        if(strPosInArray($jskos['type'], '#PlaceOrGeographicName')) {
          $entityType = 'g';
          $entityCode = 'giz';
          $vzgType = 'place';
          $foundType = true;
        }
        elseif(strPosInArray($jskos['type'], '#Person')) {
          $entityType = 'p';
          $entityCode = 'piz';
          $vzgType = 'person';
          $foundType = true;
        }
        elseif(strPosInArray($jskos['type'], '#CorporateBody')) {
          $entityType = 'b';
          $entityCode = 'kiz';
          $vzgType = 'corporate';
          $foundType = true;
        }
        // concept, groupconcept, guideterm
        elseif(strPosInArray($jskos['type'], '#GuideTerm')) {
          $entityType = 's';
          $entityCode = 'siz';
          $vzgType = 'guideterm';
          $foundType = true;
        }
        // concept, groupconcept, guideterm
        elseif(strPosInArray($jskos['type'], '#GroupConcept')) {
          $entityType = 's';
          $entityCode = 'siz';
          $vzgType = 'groupconcept';
          $foundType = true;
        }
        // concept, groupconcept, guideterm
        elseif(strPosInArray($jskos['type'], '#Concept')) {
          $entityType = 's';
          $entityCode = 'siz';
          $vzgType = 'concept';
          $foundType = true;
        }
        if($foundType) {
          $marc[] = [
              '075', ' ', ' ',
              'a', $vzgType,
              'b', $entityType,
              '2', 'gndgen'
          ];
        }
      }

      // prefLabel
      if (isset($jskos['prefLabel'])) {
          $prefLabels = $jskos['prefLabel'];
          $primary = !isset($prefLabels[$primaryLanguage]);
          foreach ($prefLabels as $code => $label) {
            if ($primary || $code === $primaryLanguage) {
              $marc[] = [ '150', '1', ' ', 'a', $label, '7', '(dpeloe)' . $code ];
              $primary = false;
            } else {
              if(!$languages || isset($languages[$code])) {
                $marc[] = [ '450', '1', ' ', 'a', $label, '7', '(dpeloe)' . $code ];
              }
            }
          }
      }

      // altLabel, hiddenlabel, editorialNote, definition, note...
      $mapLabels = function($jskosField, $marcField, $primaryLanguage) use ($jskos, &$marc) {
          foreach ($jskos[$jskosField] ?? [] as $jskoslang=>$list) {
              foreach ($list as $code=>$entry) {
                  $field = $marcField;
                  $field[] = $entry;
                  $field[] = '7';
                  $field[] = '(dpeloe)' . $jskoslang;
                  $marc[] = $field;
            }
          }
      };

      $mapLabels('altLabel',        [ '450', '1', ' ', 'a'], $primaryLanguage);
      $mapLabels('hiddenLabel',     [ '450', '1', ' ', 'a' ], $primaryLanguage);
      $mapLabels('editorialNote',   [ '667', ' ', ' ', 'a' ], $primaryLanguage);
      $mapLabels('definition',      [ '677', ' ', ' ', 'a' ], $primaryLanguage);
      $mapLabels('note',            [ '680', ' ', ' ', 'a' ], $primaryLanguage);
      $mapLabels('example',         [ '681', ' ', ' ', 'a' ], $primaryLanguage);
      $mapLabels('changeNote',      [ '682', ' ', ' ', 'a' ], $primaryLanguage);
      $mapLabels('historyNote',     [ '688', ' ', ' ', 'a' ], $primaryLanguage);

      /*
      Example from https://sru.k10plus.de/gvk!rec=2?recordSchema=picaxml&version=1.1&operation=searchRetrieve&query=pica.ppn=106193880&recordSchema=marcxml&maximumRecords=1
      <datafield tag="550" ind1=" " ind2=" ">
      <subfield code="0">(DE-627)106141015</subfield>
      <subfield code="0">(DE-576)209133600</subfield>
      <subfield code="0">(DE-588)4060055-5</subfield>
      <subfield code="a">Tiefenpsychologie</subfield>
      <subfield code="4">obal</subfield>
      <subfield code="w">r</subfield>
      <subfield code="i">Oberbegriff allgemein</subfield>
      </datafield>
      */

      // broader
      if(isset($jskos['broader'])) {
        // polyhierarchical
        foreach($jskos['broader'] as $broader) {
          if(isset($broader['uri'])) {
            $broaderLabel = array_pop($broader['prefLabel']);
            if(isset($broader['prefLabel'][$primaryLanguage])){
              $broaderLabel = $broader['prefLabel'][$primaryLanguage];
            }
            $marc[] = [
                '550', ' ', ' ',
                '0', $broader['uri'],
                'a', $broaderLabel,
                '4', 'obal',
                'w', 'r',
                'i', 'Oberbegriff allgemein'
            ];
          }
        }
      }

      // related
      /*
      Example from https://sru.k10plus.de/gvk!rec=2?recordSchema=picaxml&version=1.1&operation=searchRetrieve&query=pica.ppn=106193880&recordSchema=marcxml&maximumRecords=1
      <datafield tag="550" ind1=" " ind2=" ">
      <subfield code="0">(DE-627)105394696</subfield>
      <subfield code="0">(DE-576)20993705X</subfield>
      <subfield code="0">(DE-588)4171453-2</subfield>
      <subfield code="a">Neopsychoanalyse</subfield>
      <subfield code="4">vbal</subfield>
      <subfield code="w">r</subfield>
      <subfield code="i">Verwandter Begriff</subfield>
      </datafield>
      <datafield tag="550" ind1=" " ind2=" ">
      <subfield code="0">(DE-627)105358312</subfield>
      <subfield code="0">(DE-576)209969075</subfield>
      <subfield code="0">(DE-588)4176200-9</subfield>
      <subfield code="a">Psychoanalysmus</subfield>
      <subfield code="4">vbal</subfield>
      <subfield code="w">r</subfield>
      <subfield code="i">Verwandter Begriff</subfield>
      </datafield>
      */

      // related
      if(isset($jskos['related'])) {
        // polyhierarchical
        foreach($jskos['related'] as $related) {
          $label = '';
          if(isset($related['prefLabel'])) {
            $label = array_pop($related['prefLabel']);
          }
          $marc[] = [
              '550', ' ', ' ',
              '0', $related['uri'],
              'a', $label,
              '4', 'vbal',
              'w', 'r',
              'i', 'Verwandter Begriff'
          ];
        }
      }

      // startDate + endDate
      $startDate = $jskos['startDate'] ?? '';
      $endDate = $jskos['endDate'] ?? '';

      if ($startDate !== '' || $endDate !== '') {
        $dateStr = $startDate . '-' . $endDate;
        $type = 'http://d-nb.info/standards/elementset/gnd#dateOfBirthAndDeath';
        if($endDate == '') {
          $type = 'http://d-nb.info/standards/elementset/gnd#dateOfBirth';
        }
        if($startDate === '') {
          $type = 'http://d-nb.info/standards/elementset/gnd#dateOfDeath';
        }
        $marc[] = [
            '548', ' ', ' ', 'a', $dateStr,
            '4', 'datl',
            '4', $type,
            'w', 'r',
            'i', 'Lebensdaten',
        ];
      }

      // startPlace + endPlace --> not in jskos yet

      /*
          <datafield tag="551" ind1=" " ind2=" ">
            <subfield code="0">(DE-101)040427420</subfield>
            <subfield code="0">(DE-588)4042742-0</subfield>
            <subfield code="0">http://d-nb.info/gnd/4042742-0</subfield>
            <subfield code="a">Nürnberg</subfield>
            <subfield code="4">ortg</subfield>
            <subfield code="4">http://d-nb.info/standards/elementset/gnd#placeOfBirth</subfield>
            <subfield code="w">r</subfield>
            <subfield code="i">Geburtsort</subfield>
          </datafield>
          <datafield tag="551" ind1=" " ind2=" ">
            <subfield code="0">(DE-101)040427420</subfield>
            <subfield code="0">(DE-588)4042742-0</subfield>
            <subfield code="0">http://d-nb.info/gnd/4042742-0</subfield>
            <subfield code="a">Nürnberg</subfield>
            <subfield code="4">orts</subfield>
            <subfield code="4">http://d-nb.info/standards/elementset/gnd#placeOfDeath</subfield>
            <subfield code="w">r</subfield>
            <subfield code="i">Sterbeort</subfield>
          </datafield>
      */

      // narrower

      // literature (GVK, free literaturetext...?) --> not in jskos yet

      // order the properties by number
      usort($marc, function($a, $b) { //Sort the array using a user defined function
          $scoreA = $a[0];
          $scoreB = $b[0];
          // leader to front
          if($scoreA == 'LDR') {
            $scoreA = 0;
          }
          return $scoreA < $scoreB ? -1 : 1; //Compare the scores
      });

      $marcRecords[] = $marc;
  }

  return $marcRecords;
}

function jskos2marcjson(array $jskosRecords, array $options=[]) {
  $marcRecords = jskos2marc($jskosRecords, $options);
  return json_encode($marcRecords, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function jskos2marcxml(array $jskosRecords, array $options=[]) {
  $marcRecords = jskos2marc($jskosRecords, $options);

  $marcXML = ["<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"];

  $namespace = $options['namespace'] ?? "http://www.loc.gov/MARC21/slim";
  if ( count($marcRecords) > 1 ) {
    $marcXML[] = "<collection xmlns=\"$namespace\">\n";
    $namespace = "";
  }

  foreach ($marcRecords as $marc) {
    $marcXML[] = marcxml($marc, "Authority", $namespace);
  }

  if (count($marcRecords) > 1) {
    $marcXML[] = "</collection>\n";
  }

  return implode('', $marcXML);
}

function marcxml(array $marc, string $type, string $namespace="") {
  if ($namespace) {
    $xml = "<record xmlns=\"$namespace\" type=\"$type\">\n";
  } else {
    $xml = "<record type=\"$type\">\n";
  }
  foreach ($marc as $field) {
    if ($field[0] == 'LDR') {
      $xml .= "  <leader>".$field[4]."</leader>\n";
    }
    else if ($field[0] < 10) {
      $xml .= "  <controlfield tag=\"$field[0]\">$field[4]</controlfield>\n";
    }
    else {
      $xml .= '  <datafield tag="' . $field[0] . '" ind1="' . $field[1] . '" ind2="' . $field[2] . "\">\n";
      for ($i=3; $i<count($field); $i+=2) {
        $xml .= '    <subfield code="' . $field[$i] . '">';
        $xml .= htmlspecialchars($field[$i+1]);
        $xml .= "</subfield>\n";
      }
      $xml .= "  </datafield>\n";
    }
  }
  $xml .= "</record>\n";
  return $xml;
}

function jskos_decode($json) {
    $jskos = json_decode($json, TRUE);
    return preg_match('/^\s*{/', $json) ? [ $jskos ] : $jskos;
}

function strPosInArray($haystack = array(), $needle = '') {
    $chr = array();
    if($needle == '') {
      return false;
    }
    foreach($haystack as $haystackElement) {
      if(strpos($haystackElement, $needle) !== false) {
        return true;
      }
    }
    return false;
}

?>
