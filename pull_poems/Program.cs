using Newtonsoft.Json.Linq;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Text;
using System.Threading.Tasks;

namespace pull_poems
{
    class Program
    {
        static void Main(string[] args)
        {
            Directory.CreateDirectory("input");
            int _temp = 0;
            JObject authorsList = (JObject)LoadJSON("https://poetrydb.org/author");
            foreach (string authorName in (JArray)authorsList["authors"])
            {
                JArray authorPoems = (JArray)LoadJSON("https://poetrydb.org/author/" + authorName  + "/lines");
                List<string> allLines = new List<string>();
                foreach (JObject poem in authorPoems)
                {
                    foreach (string line in (JArray)poem["lines"])
                    {
                        if (line.Replace(" ", "") == "") continue;
                        if (int.TryParse(line, out _temp)) continue;
                        allLines.Add(line.Replace("  ", "").Replace("\t", ""));
                    }
                }
                File.WriteAllLines("input/" + authorName + ".txt", allLines);
            }
        }
        
        static private JToken LoadJSON(string url)
        {
            var request = WebRequest.Create(url);
            using (var response = request.GetResponse())
            using (var reader = new System.IO.StreamReader(response.GetResponseStream(), ASCIIEncoding.ASCII))
            {
                return JToken.Parse(reader.ReadToEnd());
            }
        }
    }
}
