To test the API with wget

wget -qO- --post-data "name=NAME2" https://pool01.mietkamera.de:8443/shorttag/update/b38211

Ermittle den Namen der Netzwerkschnittstelle
ip link | awk -F: '$0 !~ "lo|vir|wl|^[^0-9]"{print $2;getline}'

