- The configuration files here were designed for SolR 
 (http://lucene.apache.org/solr) version 1.4.
 
- Change-3.0.3 works with older SolR configurations (schema version 2.0.4, SolR 1.2-rbs)
- You MUST declare schemaVersion = 3.0.3 in your project.xml to use this schema:
  
  <config>
    <indexer>
      <SolrManager>
        <entry name="schemaVersion">3.0.3</entry>
      </SolrManager>
    </indexer>
  </config>
 
- Languages configured: de, en, es, fr, it, nl, pt

- Variables to typically adjust in production environnement:
  - solrconfig.xml: cache configuration 
    (config/query/filterCache|queryResultCache|documentCache[@size, @initialSize,]