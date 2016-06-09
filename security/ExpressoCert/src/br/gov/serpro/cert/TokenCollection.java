/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package br.gov.serpro.cert;

import br.gov.serpro.setup.Setup;
import java.util.HashMap;

/**
 *
 * @author esa
 */
class TokenCollection extends HashMap<String, Token>{
    
    private String preferedTokenKey;
    private final Setup setup;

    public TokenCollection(Setup setup){
       
        this.setup = setup;
        this.addTokens(setup.getParameter("token"));

    }

    public void setPreferedToken(java.lang.String preferedTokenKey){
        
    }

    public String getPreferedToken(){
        return preferedTokenKey;
    }

    private void addTokens(String tokens){
        
        String[] tokensArray = tokens.split(",");
        for (String tokenString : tokensArray){
            if (tokenString != null && tokenString.length() > 0){
                String[] tokenArray = tokenString.split(";");
                Token token = new Token(tokenArray[0], tokenArray[1], this.setup);
                token.registerToken();
                if (token.isRegistered()){
                    this.put(token.getName(), token);
                }
            }
        }
    }

}
