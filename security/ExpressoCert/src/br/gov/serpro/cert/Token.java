/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package br.gov.serpro.cert;

import br.gov.serpro.setup.Setup;
import java.io.ByteArrayInputStream;
import java.io.File;
import java.security.Provider;
import java.security.ProviderException;
import java.security.Security;

//TODO: Deal with wildcards for environments variables.

/**
 *
 * @author esa
 */
class Token{

    private final Setup setup;
    private String name;
    private String libraryPath;
    private Provider tokenProvider;
    private boolean registered = false;

    private Token(final Setup setup) {
        this.setup = setup;
    }

    Token(String name, String libraryPath, final Setup setup){
        this(setup);
        this.setName(name);
        this.setLibraryPath(libraryPath);
    }

    public boolean isRegistered() {
        return this.registered;
    }

    public void setLibraryPath(String libraryPath) {
        this.libraryPath = libraryPath;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getName() {
        return this.name;
    }

    protected void registerToken(){
        String tokenConfiguration = new String("name = " + name + "\n" +
            "library = " + libraryPath + "\ndisabledMechanisms = {\n" +
            "CKM_SHA1_RSA_PKCS\n}");

        try{
            this.registered = false;
            if (libraryExists()){
                Provider pkcs11Provider = new sun.security.pkcs11.SunPKCS11(new ByteArrayInputStream(tokenConfiguration.getBytes()));
                this.tokenProvider = pkcs11Provider;
                Security.addProvider(pkcs11Provider);
                this.registered = true;
            }
        }
        catch (ProviderException e){
            if (setup.getParameter("debug").equalsIgnoreCase("true")) {
                e.printStackTrace();
                System.out.println("Não foi possível inicializar o seguinte token: " + tokenConfiguration);
            }
        }
    }

    protected void unregisterToken(){
        Security.removeProvider(this.tokenProvider.getName());
    }

/*    public KeyStore getKeystore() throws KeyStoreException {

        return (this.keyStore = KeyStore.getInstance("PKCS11"));

    }
*/
    public boolean libraryExists(){

        File libraryFile = new File(libraryPath);
        if (libraryFile.exists()){
            if (setup.getParameter("debug").equalsIgnoreCase("true")) {
                System.out.println("Arquivo " + libraryPath + " existe.");
            }
            return true;
        }
        
        if (setup.getParameter("debug").equalsIgnoreCase("true")) {
            System.out.println("Biblioteca do Token/SmartCard " + name + " não foi encontrada: " + libraryPath);
        }
        
        return false;
    }
    
}
