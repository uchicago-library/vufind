<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    version="2.0">
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>
    <xsl:template match="USMARC">
        <record><xsl:apply-templates select="@*|node()"/></record>
    </xsl:template>
    <xsl:template match="VarFlds|VarCFlds|VarDFlds|NumbCode|MainEnty|Titles|Notes|SSIFlds">
       <xsl:apply-templates select="@*|node()"/>
    </xsl:template>
    <xsl:template match="*[matches(name(),'Fld00.')]" >
        <xsl:if test="normalize-space(.) != '' or ./@* != ''">
            <controlfield><xsl:attribute name="tag">
                <xsl:value-of select="replace(name(), 'Fld', '')"/>
                </xsl:attribute>
                <xsl:apply-templates select="node()"/>
            </controlfield>
        </xsl:if>
    </xsl:template>
    <xsl:template match="*[matches(name(),'Fld([0-9][1-9])|([1-9][0-9]).')]" >
        <datafield>
            <xsl:attribute name="tag"><xsl:value-of select="replace(name(), 'Fld', '')"/></xsl:attribute>
            <xsl:attribute name="ind1">
                <xsl:choose>
                    <xsl:when test="@I1='BLANK' or @I1='blank'">
                        <xsl:text> </xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="@I1"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>
            <xsl:attribute name="ind2">
                <xsl:choose>
                    <xsl:when test="@I2='BLANK' or @I2='blank'">
                        <xsl:text> </xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="@I2"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>
            <xsl:for-each select="child::node()">
                <subfield>
                    <xsl:attribute name="code">
                        <xsl:value-of select="name()"/>
                    </xsl:attribute><xsl:apply-templates select="node()"/></subfield>              
            </xsl:for-each>
        </datafield>

    </xsl:template>
    <xsl:template match="trlntocs" >
        <collection><xsl:apply-templates select="@*|node()"/></collection>
    </xsl:template>    
    <xsl:template match="summarys" >
        <collection><xsl:apply-templates select="@*|node()"/></collection>
    </xsl:template>    
    <xsl:template match="Leader" >
    </xsl:template>    
</xsl:stylesheet>
