package org.solrmarc.index;

import java.io.FileNotFoundException;
import java.io.OutputStreamWriter;
import java.io.PrintWriter;
import java.io.UnsupportedEncodingException;

import org.solrmarc.marc.MarcPrinter;

public class MixinTestFrame
{
    public static void main(String[] args) throws FileNotFoundException
    {
        MarcPrinter marcPrinter = null;
        PrintWriter pOut = null;
        try {
            pOut = new PrintWriter(new OutputStreamWriter(System.out, "UTF-8"));
            marcPrinter = new MarcPrinter(pOut);
            marcPrinter.init(args);
        }
        catch (IllegalArgumentException e)
        {
            System.err.println(e.getMessage());
            //e.printStackTrace();
            System.exit(1);
        }
        catch (UnsupportedEncodingException e)
        {
            System.err.println(e.getMessage());
            //e.printStackTrace();
            System.exit(1);
        }
        
        int exitCode = marcPrinter.handleAll();
        if (pOut != null) pOut.flush();
        System.exit(exitCode);

    }

}
