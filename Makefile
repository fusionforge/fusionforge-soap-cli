DESTDIR=/usr/local

install:
	mkdir -p $(DESTDIR)/bin
	mkdir -p $(DESTDIR)/share/fusionforge-soap-cli
	cp fusionforge-soap-cli $(DESTDIR)/bin
	cp -r nusoap $(DESTDIR)/share/fusionforge-soap-cli
	cp -r include $(DESTDIR)/share/fusionforge-soap-cli
	cat fusionforge-soap-cli | sed -e "s/define(\"NUSOAP_DIR\"[^)]*);/define(\"NUSOAP_DIR\", \"\/usr\/local\/share\/fusionforge-soap-cli\/nusoap\/lib\/\");/g" > $(DESTDIR)/bin/fusionforge-soap-cli
	cat fusionforge-soap-cli | sed -e "s/define(\"FFORGE_CLI_DIR\"[^)]*);/define(\"FFORGE_CLI_DIR\", \"\/usr\/local\/bin\/share\/fusionforge-soap-cli\/include\/\");/g" > $(DESTDIR)/bin/fusionforge-soap-cli

