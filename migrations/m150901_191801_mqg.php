<?php

use yii\db\Schema;
use yii\db\Migration;

class m150901_191801_mqg extends Migration
{
    public function up()
    {
	$this->execute("
CREATE TABLE IF NOT EXISTS `device` (
  `domain` varchar(60) NOT NULL,
  `node` varchar(200) NOT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `address` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `device` (`domain`, `node`, `lat`, `lng`, `address`) VALUES
('cipo.rnp.br', 'AJU', -11.0014, -37.1035, 'State of Sergipe, Brazil'),
('cipo.rnp.br', 'BHE', -19.9027, -43.964, 'State of Minas Gerais, Brazil'),
('cipo.rnp.br', 'BLM', -1.3858, -48.4833, 'State of Pará, Brazil'),
('cipo.rnp.br', 'BSA', -15.8267, -47.9218, 'Federal District, Brazil'),
('cipo.rnp.br', 'BSA2', -15.8267, -47.9218, 'Federal District, Brazil'),
('cipo.rnp.br', 'BVB', 2.80719, -60.6966, 'State of Roraima, Brazil'),
('cipo.rnp.br', 'CGB', -15.6144, -56.0418, 'State of Mato Grosso, Brazil'),
('cipo.rnp.br', 'CGE', -7.24284, -35.9016, 'State of Paraíba, Brazil'),
('cipo.rnp.br', 'CGR', -20.4686, -54.6224, 'State of Mato Grosso do Sul, Brazil'),
('cipo.rnp.br', 'CTA', -25.4951, -49.2898, 'State of Paraná, Brazil'),
('cipo.rnp.br', 'FLA', -3.79135, -38.5192, 'State of Ceará, Brazil'),
('cipo.rnp.br', 'FNS', -27.6103, -48.4846, 'State of Santa Catarina, Brazil'),
('cipo.rnp.br', 'GNA', -16.685, -49.2684, 'State of Goiás, Brazil'),
('cipo.rnp.br', 'JPA', -7.1466, -34.8816, 'State of Paraíba, Brazil'),
('cipo.rnp.br', 'MAO', -3.04466, -59.9671, 'State of Amazonas, Brazil'),
('cipo.rnp.br', 'MCP', 0.101772, -51.0969, 'State of Amapá, Brazil'),
('cipo.rnp.br', 'MCZ', -9.59344, -35.6867, 'State of Alagoas, Brazil'),
('cipo.rnp.br', 'NTL', -5.79991, -35.2222, 'State of Rio Grande do Norte, Brazil'),
('cipo.rnp.br', 'PMW', -10.1753, -48.2982, 'State of Tocantins, Brazil'),
('cipo.rnp.br', 'POA', -30.0346, -51.2177, 'State of Rio Grande do Sul, Brazil'),
('cipo.rnp.br', 'PVH', -8.75655, -63.8549, 'State of Rondônia, Brazil'),
('cipo.rnp.br', 'RBR', -9.98633, -67.8311, 'State of Acre, Brazil'),
('cipo.rnp.br', 'REC', -8.04331, -34.9362, 'State of Pernambuco, Brazil'),
('cipo.rnp.br', 'RJO', -22.9139, -43.2094, 'State of Rio de Janeiro, Brazil'),
('cipo.rnp.br', 'SDR', -12.9016, -38.4198, 'State of Bahia, Brazil'),
('cipo.rnp.br', 'SLS', -2.55857, -44.2918, 'State of Maranhão, Brazil'),
('cipo.rnp.br', 'SPO', -23.5432, -46.6292, 'State of São Paulo, Brazil'),
('cipo.rnp.br', 'SPO2', -23.5432, -46.6292, 'State of São Paulo, Brazil'),
('cipo.rnp.br', 'THE', -5.13554, -42.7915, 'State of Piauí, Brazil'),
('cipo.rnp.br', 'VTA', -20.2822, -40.2862, 'State of Espírito Santo, Brazil'),
('es.net', 'chic-cr5', 41.8855, -87.6337, NULL);
CREATE TABLE IF NOT EXISTS `domain` (
  `name` varchar(60) NOT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `address` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `domain` (`name`, `lat`, `lng`, `address`) VALUES
('aist.go.jp', 36.06, 140.133, NULL),
('ampath.net', 25.7581, -80.3762, 'Florida International University, 11200 Southwest 8th Street, Miami, FL 33199, USA'),
('caltech.edu', 34.136, -118.125, NULL),
('cipo.rnp.br', -22.8621, -43.2297, NULL),
('czechlight.cesnet.cz', 50.0833, 14.4167, ' CESNET z.s.p.o., CZ'),
('deic.dk', 55.6761, 12.5683, ' Forskningsnettet - Danish network for Research and Education, DK'),
('es.net', 37.8769, -122.25, NULL),
('funet.fi', 60.2048, 24.6679, NULL),
('geant.net', 49.1, 8.24, NULL),
('grnet.gr', 37.9833, 23.7333, ' Greek Research and Technology Network S.A, Athens, Attiki, GR'),
('heanet.ie', 53.3478, -6.2597, ' HEAnet Limited, IE'),
('icair.org', 41.8952, -87.6168, NULL),
('ja.net', 51.5775, -1.31174, NULL),
('jgn-x.jp', 35.69, 137.765, NULL),
('kddilabs.jp', 35.879, 139.517, NULL),
('krlight.net', 36.366, 127.359, NULL),
('manlan.internet2.edu', 40.7187, -74.003, NULL),
('netherlight.net', 52.3567, 4.95459, NULL),
('nordu.net', 59.3294, 18.0686, ' NORDUnet, SE'),
('oess.dcn.umnet.umich.edu', 42.2768, -83.7367, NULL),
('pionier.net.pl', 52.4167, 16.9667, ' Institute of Bioorganic Chemistry Polish Academy of Science, Poznan  Supercomputing and Networking Center, Poznan, Wielkopolskie, PL'),
('pionier.pl', 52.2333, 21.0167, ' Kylos sp. z o.o., PL'),
('sinet.ac.jp', 35.693, 139.758, NULL),
('southernlight.net.br', -23.5551, -46.6717, 'Fundação Amparo a Pesquisa do Estado São Paulo - Rua Doutor Ovídio Pires de Campos, 215 - Jardim America, São Paulo - SP, 05403-010, Brazil'),
('surfnet.nl', 52.3567, 4.95459, NULL),
('ufrgs.br', -30.0685, -51.1201, NULL),
('uvalight.net', 52.3667, 4.9, ' SURFnet, The Netherlands, NL'),
('wix.internet2.edu', 38.92, -77.2116, NULL);
CREATE TABLE IF NOT EXISTS `subscription` (
  `id` int(11) NOT NULL,
  `nsa` varchar(200) NOT NULL,
  `discovery_url` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
ALTER TABLE `device`
  ADD PRIMARY KEY (`domain`,`node`);
ALTER TABLE `domain`
  ADD PRIMARY KEY (`name`);
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nsa` (`nsa`);
ALTER TABLE `subscription`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;	
");
    }

    public function down()
    {
        echo "m150901_191801_mqg cannot be reverted.\n";

        return false;
    }
    
}
